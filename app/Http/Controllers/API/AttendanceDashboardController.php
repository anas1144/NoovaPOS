<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\Attendance;
use App\Models\AttendanceTask;
use App\Models\Employee;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Attendance dashboard widgets, the live board, and attendance history records.
 * All queries are tenant-scoped via the models.
 */
class AttendanceDashboardController extends AppBaseController
{
    public function __construct(private readonly AttendanceService $attendance)
    {
    }

    public function dashboard(Request $request): JsonResponse
    {
        $today = Carbon::today();
        $totalEmployees = Employee::query()->where('status', true)->count();

        $todays = Attendance::query()->whereDate('date', $today)->get();

        $present  = $todays->where('check_in_at', '!=', null)->count();
        $late     = $todays->where('status', Attendance::STATUS_LATE)->count();
        $onBreak  = $todays->where('on_break', true)->count();
        $working  = $todays->filter(fn ($a) => $a->check_in_at && ! $a->check_out_at && ! $a->on_break)->count();
        $absent   = max(0, $totalEmployees - $present);
        $workHours = round($todays->sum(fn ($a) => $this->attendance->workingSeconds($a)) / 3600, 1);

        $activeTasks = AttendanceTask::query()->where('status', AttendanceTask::STATUS_RUNNING)->count();
        $completedTasks = AttendanceTask::query()
            ->where('status', AttendanceTask::STATUS_COMPLETED)
            ->whereDate('updated_at', $today)->count();

        $attendancePct = $totalEmployees > 0 ? round(($present / $totalEmployees) * 100) : 0;

        return $this->sendResponse([
            'cards' => [
                'present'             => $present,
                'absent'              => $absent,
                'late'                => $late,
                'on_break'            => $onBreak,
                'working'             => $working,
                'active_tasks'        => $activeTasks,
                'completed_tasks'     => $completedTasks,
                'total_work_hours'    => $workHours,
                'attendance_percent'  => $attendancePct,
                'total_employees'     => $totalEmployees,
            ],
            'timeline' => $this->timeline($today),
        ], 'Attendance dashboard retrieved.');
    }

    /** Currently active employees for the live board. */
    public function live(Request $request): JsonResponse
    {
        $rows = Attendance::query()
            ->whereDate('date', Carbon::today())
            ->whereNotNull('check_in_at')
            ->whereNull('check_out_at')
            ->with('employee:id,first_name,last_name,employee_code')
            ->get()
            ->map(function ($a) {
                $task = $a->activeTask();
                return [
                    'attendance_id'   => $a->id,
                    'employee_id'     => $a->employee_id,
                    'name'            => $a->employee?->full_name,
                    'employee_code'   => $a->employee?->employee_code,
                    'check_in_at'     => $a->check_in_at,
                    'working_seconds' => $this->attendance->workingSeconds($a),
                    'state'           => $a->on_break ? 'on_break' : ($task ? 'on_task' : 'working'),
                    'active_task'     => $task?->name,
                ];
            });

        return $this->sendResponse($rows, 'Live attendance retrieved.');
    }

    /** Attendance history with optional date-range + employee filters. */
    public function records(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from'        => 'nullable|date',
            'to'          => 'nullable|date',
            'employee_id' => 'nullable|integer',
        ]);

        $rows = Attendance::query()
            ->with('employee:id,first_name,last_name,employee_code')
            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($data['to'] ?? null, fn ($q, $d) => $q->whereDate('date', '<=', $d))
            ->when($data['employee_id'] ?? null, fn ($q, $id) => $q->where('employee_id', $id))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate((int) $request->get('page_size', 25));

        return $this->sendResponse($rows, 'Attendance records retrieved.');
    }

    private function timeline(Carbon $today): array
    {
        $events = [];

        $atts = Attendance::query()->whereDate('date', $today)
            ->with('employee:id,first_name,last_name')->get();
        foreach ($atts as $a) {
            $name = $a->employee?->full_name ?? 'Employee';
            if ($a->check_in_at) {
                $events[] = ['at' => $a->check_in_at, 'text' => "{$name} checked in", 'type' => 'in'];
            }
            if ($a->check_out_at) {
                $events[] = ['at' => $a->check_out_at, 'text' => "{$name} checked out", 'type' => 'out'];
            }
        }

        $tasks = AttendanceTask::query()->whereDate('created_at', $today)
            ->with('employee:id,first_name,last_name')->get();
        foreach ($tasks as $t) {
            $name = $t->employee?->full_name ?? 'Employee';
            $verb = $t->status === AttendanceTask::STATUS_COMPLETED ? 'completed' : 'started';
            $events[] = ['at' => $t->updated_at, 'text' => "{$name} {$verb} " . ucfirst($t->category) . " Task", 'type' => 'task'];
        }

        usort($events, fn ($a, $b) => strcmp((string) $b['at'], (string) $a['at']));

        return array_slice($events, 0, 20);
    }
}
