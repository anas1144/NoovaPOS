<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Core attendance clock logic shared by the kiosk, dashboard and reports.
 *
 * All reads/writes go through tenant-scoped models (Multitenantable), so data is
 * automatically isolated to the current tenant.
 */
class AttendanceService
{
    public function __construct(private readonly AttendanceSettingService $settings)
    {
    }

    /** The Employee record linked to the logged-in user (or null). */
    public function currentEmployee(): ?Employee
    {
        $userId = Auth::id();
        if (! $userId) {
            return null;
        }
        return Employee::query()->where('user_id', $userId)->first();
    }

    public function todayAttendance(Employee $employee): ?Attendance
    {
        return Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', Carbon::today())
            ->first();
    }

    /**
     * Seconds worked so far today: from check-in to now (or check-out), minus
     * accumulated break time.
     */
    public function workingSeconds(?Attendance $a): int
    {
        if (! $a || ! $a->check_in_at) {
            return 0;
        }
        $end = $a->check_out_at ?: now();
        $gross = max(0, $a->check_in_at->diffInSeconds($end));
        return max(0, $gross - (int) $a->break_seconds);
    }

    /**
     * A compact status payload for the kiosk / dashboard for one employee.
     */
    public function statusPayload(Employee $employee): array
    {
        $a = $this->todayAttendance($employee);
        $activeTask = $a?->activeTask();
        $openBreak = $a?->openBreak();

        return [
            'employee' => [
                'id'    => $employee->id,
                'name'  => $employee->full_name,
                'code'  => $employee->employee_code,
                'photo' => null,
            ],
            'has_record'       => (bool) $a,
            'checked_in'       => (bool) ($a && $a->check_in_at && ! $a->check_out_at),
            'checked_out'      => (bool) ($a && $a->check_out_at),
            'on_break'         => (bool) $openBreak,
            'check_in_at'      => $a?->check_in_at,
            'check_out_at'     => $a?->check_out_at,
            'working_seconds'  => $this->workingSeconds($a),
            'break_seconds'    => (int) ($a?->break_seconds ?? 0),
            'active_task'      => $activeTask ? [
                'id'       => $activeTask->id,
                'name'     => $activeTask->name,
                'category' => $activeTask->category,
                'status'   => $activeTask->status,
            ] : null,
        ];
    }

    /**
     * First action of the day: clock the employee in. Returns the Attendance.
     */
    public function checkIn(Employee $employee, string $method, ?string $category = null, ?int $createdBy = null): Attendance
    {
        $a = $this->todayAttendance($employee);

        if ($a && $a->check_in_at && ! $a->check_out_at
            && $this->settings->enabled('prevent_multiple_checkins', $employee->tenant_id)) {
            // Already on the clock — idempotent, just return it.
            return $a;
        }

        $now = now();
        $isLate = $now->hour >= 10; // simple late rule; shift rules can refine later

        $a = $a ?: new Attendance([
            'employee_id' => $employee->id,
            'date'        => Carbon::today()->toDateString(),
        ]);
        $a->fill([
            'employee_id'     => $employee->id,
            'store_id'        => $employee->store_id,
            'shop_id'         => $employee->shop_id,
            'date'            => $a->date ?: Carbon::today()->toDateString(),
            'check_in_at'     => $now,
            'check_out_at'    => null,
            'check_in_method' => $method,
            'work_category'   => $category,
            'status'          => $isLate ? Attendance::STATUS_LATE : Attendance::STATUS_PRESENT,
            'on_break'        => false,
            'created_by'      => $createdBy,
        ]);
        $a->save();

        return $a;
    }

    public function checkOut(Employee $employee, string $method): ?Attendance
    {
        $a = $this->todayAttendance($employee);
        if (! $a || ! $a->check_in_at) {
            return null;
        }

        // Close any open break first.
        if ($open = $a->openBreak()) {
            $this->endBreak($a);
            $a->refresh();
        }

        $a->check_out_at = now();
        $a->check_out_method = $method;
        $a->work_seconds = $this->workingSeconds($a);
        $a->hours_worked = round($a->work_seconds / 3600, 2);
        $a->save();

        return $a;
    }

    public function startBreak(Attendance $a): AttendanceBreak
    {
        // Don't double-open.
        if ($existing = $a->openBreak()) {
            return $existing;
        }
        $break = AttendanceBreak::create([
            'attendance_id' => $a->id,
            'employee_id'   => $a->employee_id,
            'started_at'    => now(),
        ]);
        $a->on_break = true;
        $a->save();

        return $break;
    }

    public function endBreak(Attendance $a): ?AttendanceBreak
    {
        $break = $a->openBreak();
        if (! $break) {
            return null;
        }
        $break->ended_at = now();
        $break->duration_seconds = max(0, $break->started_at->diffInSeconds($break->ended_at));
        $break->save();

        $a->break_seconds = (int) $a->break_seconds + (int) $break->duration_seconds;
        $a->on_break = false;
        $a->save();

        return $break;
    }
}
