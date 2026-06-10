<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\Attendance;
use App\Models\AttendanceTask;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Attendance reporting: a date-range summary, a per-employee productivity
 * breakdown, and a performance table (attendance rate, avg hours, task
 * completion). All tenant-scoped via the models.
 */
class AttendanceReportController extends AppBaseController
{
    private function range(Request $request): array
    {
        $to = $request->filled('to') ? Carbon::parse($request->get('to')) : Carbon::today();
        $from = $request->filled('from') ? Carbon::parse($request->get('from')) : (clone $to)->subDays(29);
        return [$from->startOfDay(), $to->endOfDay()];
    }

    /** Headline totals + a daily series for the range. */
    public function summary(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        $rows = Attendance::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->get('employee_id')))
            ->get();

        $byDay = [];
        foreach ($rows as $a) {
            $key = Carbon::parse($a->date)->toDateString();
            $byDay[$key] = $byDay[$key] ?? ['date' => $key, 'present' => 0, 'late' => 0, 'hours' => 0];
            if ($a->check_in_at) $byDay[$key]['present'] += 1;
            if ($a->status === Attendance::STATUS_LATE) $byDay[$key]['late'] += 1;
            $byDay[$key]['hours'] += (float) $a->hours_worked;
        }
        ksort($byDay);

        return $this->sendResponse([
            'from'    => $from->toDateString(),
            'to'      => $to->toDateString(),
            'totals'  => [
                'records'    => $rows->count(),
                'present'    => $rows->where('check_in_at', '!=', null)->count(),
                'late'       => $rows->where('status', Attendance::STATUS_LATE)->count(),
                'work_hours' => round($rows->sum(fn ($a) => (float) $a->hours_worked), 1),
            ],
            'daily'   => array_values($byDay),
        ], 'Attendance summary retrieved.');
    }

    /** Productivity: work / break / task hours + overtime over the range. */
    public function productivity(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        $rows = Attendance::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->get('employee_id')))
            ->get();

        $workSecs  = (int) $rows->sum('work_seconds');
        $breakSecs = (int) $rows->sum('break_seconds');
        $taskSecs  = (int) $rows->sum('task_seconds');

        // Overtime: hours beyond 8h/working-day.
        $days = max(1, $rows->pluck('date')->unique()->count());
        $overtime = max(0, round($workSecs / 3600 - $days * 8, 1));

        return $this->sendResponse([
            'work_hours'  => round($workSecs / 3600, 1),
            'break_hours' => round($breakSecs / 3600, 1),
            'task_hours'  => round($taskSecs / 3600, 1),
            'overtime'    => $overtime,
        ], 'Productivity retrieved.');
    }

    /** Per-employee performance table. */
    public function performance(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);
        $workingDays = max(1, $from->diffInDaysFiltered(
            fn (Carbon $d) => ! $d->isWeekend(),
            $to
        ));

        $employees = Employee::query()->where('status', true)->get(['id', 'first_name', 'last_name', 'employee_code']);

        $data = $employees->map(function ($e) use ($from, $to, $workingDays) {
            $atts = Attendance::query()
                ->where('employee_id', $e->id)
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->get();

            $present = $atts->where('check_in_at', '!=', null)->count();
            $hours = round($atts->sum(fn ($a) => (float) $a->hours_worked), 1);
            $tasks = AttendanceTask::query()->where('employee_id', $e->id)
                ->whereBetween('created_at', [$from, $to])->get();
            $completed = $tasks->where('status', AttendanceTask::STATUS_COMPLETED)->count();

            return [
                'employee_id'   => $e->id,
                'name'          => trim("{$e->first_name} {$e->last_name}"),
                'employee_code' => $e->employee_code,
                'present_days'  => $present,
                'attendance_rate' => round(($present / $workingDays) * 100),
                'avg_hours'     => $present > 0 ? round($hours / $present, 1) : 0,
                'tasks'         => $tasks->count(),
                'task_completion' => $tasks->count() > 0 ? round(($completed / $tasks->count()) * 100) : 0,
            ];
        });

        return $this->sendResponse([
            'from' => $from->toDateString(),
            'to'   => $to->toDateString(),
            'rows' => $data->values(),
        ], 'Performance retrieved.');
    }
}
