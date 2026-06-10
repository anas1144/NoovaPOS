<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\AttendanceTask;
use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Task tracking during an attendance day: start / pause / resume / complete,
 * with accumulated duration. Only one task runs at a time per employee — starting
 * a new one pauses the previous.
 */
class AttendanceTaskController extends AppBaseController
{
    public function __construct(private readonly AttendanceService $attendance)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $employee = $this->employee($request);
        if (! $employee) {
            return $this->sendResponse([], 'No employee linked to this user.');
        }
        $a = $this->attendance->todayAttendance($employee);

        $tasks = $a
            ? AttendanceTask::query()->where('attendance_id', $a->id)->latest('id')->get()
            : collect();

        return $this->sendResponse($tasks, 'Tasks retrieved.');
    }

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:191',
            'category'    => 'required|in:office,personal',
            'employee_id' => 'nullable|integer',
        ]);

        $employee = $this->employee($request);
        if (! $employee) {
            return $this->sendError('No employee linked to this user.', 422);
        }

        $a = $this->attendance->todayAttendance($employee);
        if (! $a || ! $a->check_in_at || $a->check_out_at) {
            return $this->sendError('Check in before starting a task.', 422);
        }

        // Pause any currently running task.
        if ($running = $a->activeTask()) {
            $this->accumulate($running);
            $running->status = AttendanceTask::STATUS_PAUSED;
            $running->save();
        }

        $task = AttendanceTask::create([
            'attendance_id' => $a->id,
            'employee_id'   => $employee->id,
            'name'          => $data['name'],
            'category'      => $data['category'],
            'status'        => AttendanceTask::STATUS_RUNNING,
            'started_at'    => now(),
            'resumed_at'    => now(),
        ]);

        return $this->sendResponse($task, 'Task started.');
    }

    public function pause(Request $request, AttendanceTask $task): JsonResponse
    {
        $this->accumulate($task);
        $task->status = AttendanceTask::STATUS_PAUSED;
        $task->save();

        return $this->sendResponse($task, 'Task paused.');
    }

    public function resume(Request $request, AttendanceTask $task): JsonResponse
    {
        // Pause whatever else is running for this attendance first.
        if ($task->attendance && ($running = $task->attendance->activeTask()) && $running->id !== $task->id) {
            $this->accumulate($running);
            $running->status = AttendanceTask::STATUS_PAUSED;
            $running->save();
        }

        $task->status = AttendanceTask::STATUS_RUNNING;
        $task->resumed_at = now();
        $task->save();

        return $this->sendResponse($task, 'Task resumed.');
    }

    public function complete(Request $request, AttendanceTask $task): JsonResponse
    {
        $data = $request->validate(['notes' => 'nullable|string|max:1000']);

        $this->accumulate($task);
        $task->status = AttendanceTask::STATUS_COMPLETED;
        $task->ended_at = now();
        if (isset($data['notes'])) {
            $task->notes = $data['notes'];
        }
        $task->save();

        // Roll the task time up onto the attendance day.
        if ($task->attendance) {
            $task->attendance->task_seconds = (int) AttendanceTask::query()
                ->where('attendance_id', $task->attendance_id)
                ->sum('duration_seconds');
            $task->attendance->save();
        }

        return $this->sendResponse($task, 'Task completed.');
    }

    /** Add the elapsed running time to the task's accumulated duration. */
    private function accumulate(AttendanceTask $task): void
    {
        if ($task->status !== AttendanceTask::STATUS_RUNNING) {
            return;
        }
        $from = $task->resumed_at ?: $task->started_at;
        if ($from) {
            $task->duration_seconds = (int) $task->duration_seconds + max(0, $from->diffInSeconds(now()));
        }
    }

    private function employee(Request $request): ?Employee
    {
        $employeeId = $request->input('employee_id');
        if ($employeeId && ($request->user()?->can('attendance.task.update') || $request->user()?->can('attendance.manual'))) {
            return Employee::query()->find($employeeId);
        }
        return $this->attendance->currentEmployee();
    }
}
