<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Employee;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Attendance requests: employees submit corrections; managers approve/reject.
 * Approving a correction writes the attendance record.
 */
class AttendanceRequestController extends AppBaseController
{
    public function __construct(private readonly AttendanceService $attendance)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $canManage = $request->user()?->can('attendance.edit') || $request->user()?->can('attendance.manual');

        $query = AttendanceRequest::query()
            ->with('employee:id,first_name,last_name,employee_code')
            ->latest('id');

        // Normal employees only see their own requests.
        if (! $canManage) {
            $self = $this->attendance->currentEmployee();
            $query->where('employee_id', $self?->id ?? 0);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        return $this->sendResponse($query->paginate((int) $request->get('page_size', 25)), 'Attendance requests retrieved.');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'                => 'required|in:correction,missing_checkout,leave,other',
            'date'                => 'required|date',
            'requested_check_in'  => 'nullable|date',
            'requested_check_out' => 'nullable|date',
            'reason'              => 'nullable|string|max:1000',
            'employee_id'         => 'nullable|integer',
        ]);

        // Employees may only file for themselves; managers may file for anyone.
        $canManage = $request->user()?->can('attendance.edit') || $request->user()?->can('attendance.manual');
        $employee = ($canManage && ! empty($data['employee_id']))
            ? Employee::query()->find($data['employee_id'])
            : $this->attendance->currentEmployee();

        if (! $employee) {
            return $this->sendError('No employee linked to this user.', 422);
        }

        $req = AttendanceRequest::create([
            'employee_id'         => $employee->id,
            'type'                => $data['type'],
            'date'                => Carbon::parse($data['date'])->toDateString(),
            'requested_check_in'  => $data['requested_check_in'] ?? null,
            'requested_check_out' => $data['requested_check_out'] ?? null,
            'reason'              => $data['reason'] ?? null,
            'status'              => AttendanceRequest::STATUS_PENDING,
        ]);

        return $this->sendResponse($req, 'Request submitted.');
    }

    public function approve(Request $request, AttendanceRequest $attendanceRequest): JsonResponse
    {
        if (! ($request->user()?->can('attendance.edit') || $request->user()?->can('attendance.manual'))) {
            return $this->sendError('Not allowed to review requests.', 403);
        }

        $data = $request->validate(['review_note' => 'nullable|string|max:500']);

        // Apply correction / missing-checkout to the attendance record.
        if (in_array($attendanceRequest->type, ['correction', 'missing_checkout'], true)) {
            $employee = Employee::query()->find($attendanceRequest->employee_id);
            if ($employee) {
                $a = Attendance::updateOrCreate(
                    ['employee_id' => $employee->id, 'date' => $attendanceRequest->date->toDateString()],
                    [
                        'store_id'        => $employee->store_id,
                        'shop_id'         => $employee->shop_id,
                        'check_in_at'     => $attendanceRequest->requested_check_in,
                        'check_out_at'    => $attendanceRequest->requested_check_out,
                        'status'          => Attendance::STATUS_PRESENT,
                        'check_in_method' => 'manual',
                        'created_by'      => $request->user()->id,
                    ]
                );
                if ($a->check_in_at && $a->check_out_at) {
                    $a->work_seconds = max(0, Carbon::parse($a->check_in_at)->diffInSeconds(Carbon::parse($a->check_out_at)) - (int) $a->break_seconds);
                    $a->hours_worked = round($a->work_seconds / 3600, 2);
                    $a->save();
                }
            }
        }

        $attendanceRequest->update([
            'status'      => AttendanceRequest::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'review_note' => $data['review_note'] ?? null,
        ]);

        return $this->sendResponse($attendanceRequest, 'Request approved.');
    }

    public function reject(Request $request, AttendanceRequest $attendanceRequest): JsonResponse
    {
        if (! ($request->user()?->can('attendance.edit') || $request->user()?->can('attendance.manual'))) {
            return $this->sendError('Not allowed to review requests.', 403);
        }

        $data = $request->validate(['review_note' => 'nullable|string|max:500']);

        $attendanceRequest->update([
            'status'      => AttendanceRequest::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'review_note' => $data['review_note'] ?? null,
        ]);

        return $this->sendResponse($attendanceRequest, 'Request rejected.');
    }
}
