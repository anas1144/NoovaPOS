<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\DeviceConnector;
use App\Services\AttendanceService;
use App\Services\AttendanceSettingService;
use App\Services\DeviceConnectorService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POS-integrated attendance: identify → check-in / check-out / break, plus the
 * self-service and admin-manual flows.
 *
 * Self vs admin: a normal employee may only act on their own record. Acting on
 * another employee requires the `attendance.manual` permission AND the
 * `allow_admin_manual` setting.
 */
class AttendanceController extends AppBaseController
{
    public function __construct(
        private readonly AttendanceService $attendance,
        private readonly AttendanceSettingService $settings,
        private readonly DeviceConnectorService $devices,
    ) {
    }

    /** Methods enabled + the current user's own status (for the kiosk header). */
    public function status(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request, optionalSelf: true);
        $settings = $this->settings->all($request->user()?->tenant_id);

        return $this->sendResponse([
            'settings' => $settings,
            'self'     => $employee ? $this->attendance->statusPayload($employee) : null,
            'is_admin' => $this->canManage($request),
        ], 'Attendance status retrieved.');
    }

    /**
     * Identify an employee by a scan method, returning their current status so
     * the kiosk can decide which actions to show.
     *
     *  - barcode / qr : `code` → Employee.attendance_code | employee_code
     *  - face         : client already matched → `employee_id`
     *  - manual/self  : the logged-in user's employee
     */
    public function identify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'method'       => 'required|in:face,fingerprint,barcode,qr,manual,device,webauthn',
            'code'         => 'nullable|string|max:191',
            'employee_id'  => 'nullable|integer',
            'connector_id' => 'nullable|integer',
            'vars'         => 'nullable|array',
        ]);

        $employee = $this->locateEmployee($request, $data);
        if (! $employee) {
            return $this->sendError('Employee not recognised.', 404);
        }

        // Non-admins may only identify themselves when self-only is on.
        if (! $this->canManage($request) && $this->settings->enabled('self_only', $request->user()?->tenant_id)) {
            $self = $this->attendance->currentEmployee();
            if (! $self || $self->id !== $employee->id) {
                return $this->sendError('You can only mark your own attendance.', 403);
            }
        }

        return $this->sendResponse(
            $this->attendance->statusPayload($employee),
            'Employee identified.'
        );
    }

    public function checkIn(Request $request): JsonResponse
    {
        $data = $request->validate([
            'method'       => 'required|in:face,fingerprint,barcode,qr,manual,device,webauthn',
            'employee_id'  => 'nullable|integer',
            'code'         => 'nullable|string|max:191',
            'connector_id' => 'nullable|integer',
            'vars'         => 'nullable|array',
            'category'     => 'nullable|in:office,personal',
        ]);

        $employee = $this->guardedEmployee($request, $data);
        if ($employee instanceof JsonResponse) {
            return $employee;
        }

        $createdBy = $this->isOther($request, $employee) ? $request->user()->id : null;
        $attendance = $this->attendance->checkIn($employee, $data['method'], $data['category'] ?? null, $createdBy);

        return $this->sendResponse([
            'attendance' => $attendance,
            'status'     => $this->attendance->statusPayload($employee),
        ], 'Checked in.');
    }

    public function checkOut(Request $request): JsonResponse
    {
        $data = $request->validate([
            'method'       => 'required|in:face,fingerprint,barcode,qr,manual,device,webauthn',
            'employee_id'  => 'nullable|integer',
            'code'         => 'nullable|string|max:191',
            'connector_id' => 'nullable|integer',
            'vars'         => 'nullable|array',
        ]);

        $employee = $this->guardedEmployee($request, $data);
        if ($employee instanceof JsonResponse) {
            return $employee;
        }

        $attendance = $this->attendance->checkOut($employee, $data['method']);
        if (! $attendance) {
            return $this->sendError('No active check-in to close.', 422);
        }

        return $this->sendResponse([
            'attendance' => $attendance,
            'status'     => $this->attendance->statusPayload($employee),
        ], 'Checked out.');
    }

    public function startBreak(Request $request): JsonResponse
    {
        return $this->breakAction($request, true);
    }

    public function endBreak(Request $request): JsonResponse
    {
        return $this->breakAction($request, false);
    }

    /**
     * Admin manual create / correction of an attendance record.
     */
    public function manual(Request $request): JsonResponse
    {
        if (! $this->canManage($request)) {
            return $this->sendError('You are not allowed to enter attendance for others.', 403);
        }

        $data = $request->validate([
            'employee_id'  => 'required|integer',
            'date'         => 'required|date',
            'check_in_at'  => 'nullable|date',
            'check_out_at' => 'nullable|date',
            'status'       => 'nullable|in:present,absent,leave,half_day,late',
            'note'         => 'nullable|string|max:1000',
        ]);

        $employee = Employee::query()->find($data['employee_id']);
        if (! $employee) {
            return $this->sendError('Employee not found.', 404);
        }

        $a = Attendance::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => Carbon::parse($data['date'])->toDateString()],
            [
                'store_id'     => $employee->store_id,
                'shop_id'      => $employee->shop_id,
                'check_in_at'  => $data['check_in_at'] ?? null,
                'check_out_at' => $data['check_out_at'] ?? null,
                'status'       => $data['status'] ?? Attendance::STATUS_PRESENT,
                'note'         => $data['note'] ?? null,
                'check_in_method' => 'manual',
                'created_by'   => $request->user()->id,
            ]
        );

        if ($a->check_in_at && $a->check_out_at) {
            $a->work_seconds = max(0, Carbon::parse($a->check_in_at)->diffInSeconds(Carbon::parse($a->check_out_at)) - (int) $a->break_seconds);
            $a->hours_worked = round($a->work_seconds / 3600, 2);
            $a->save();
        }

        return $this->sendResponse($a, 'Attendance saved.');
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function breakAction(Request $request, bool $start): JsonResponse
    {
        if (! $this->settings->enabled('enable_break_tracking', $request->user()?->tenant_id)) {
            return $this->sendError('Break tracking is disabled.', 422);
        }

        $data = $request->validate([
            'employee_id' => 'nullable|integer',
            'code'        => 'nullable|string|max:191',
        ]);

        $employee = $this->guardedEmployee($request, $data + ['method' => 'manual']);
        if ($employee instanceof JsonResponse) {
            return $employee;
        }

        $a = $this->attendance->todayAttendance($employee);
        if (! $a || ! $a->check_in_at || $a->check_out_at) {
            return $this->sendError('Employee is not currently checked in.', 422);
        }

        $start ? $this->attendance->startBreak($a) : $this->attendance->endBreak($a);

        return $this->sendResponse(
            $this->attendance->statusPayload($employee),
            $start ? 'Break started.' : 'Break ended.'
        );
    }

    /** Resolve the employee for a self-or-admin action, enforcing permissions. */
    private function guardedEmployee(Request $request, array $data)
    {
        $employee = $this->locateEmployee($request, $data);
        if (! $employee) {
            return $this->sendError('Employee not recognised.', 404);
        }

        if ($this->isOther($request, $employee)) {
            if (! $this->canManage($request)) {
                return $this->sendError('You can only mark your own attendance.', 403);
            }
        }

        return $employee;
    }

    /** Find an employee from connector/method/code/employee_id, else the current user. */
    private function locateEmployee(Request $request, array $data): ?Employee
    {
        if (! empty($data['employee_id'])) {
            return Employee::query()->find($data['employee_id']);
        }

        // No-code device connector (server-run): verify via the configured API
        // and resolve the returned identifier to an employee.
        if (! empty($data['connector_id'])) {
            $connector = DeviceConnector::query()->where('status', true)->find($data['connector_id']);
            if ($connector) {
                $result = $this->devices->verify($connector, (array) ($data['vars'] ?? []));
                if (! empty($result['matched']) && $result['employee'] !== null) {
                    return $this->findByIdentifier($result['employee']);
                }
                return null;
            }
        }

        $code = $data['code'] ?? null;
        if ($code && in_array($data['method'] ?? null, ['barcode', 'qr'], true)) {
            return $this->findByIdentifier($code);
        }

        return $this->attendance->currentEmployee();
    }

    /** Resolve a numeric employee id or a code (attendance_code / employee_code). */
    private function findByIdentifier($identifier): ?Employee
    {
        if (is_numeric($identifier)) {
            $byId = Employee::query()->find((int) $identifier);
            if ($byId) {
                return $byId;
            }
        }
        return Employee::query()
            ->where('attendance_code', $identifier)
            ->orWhere('employee_code', $identifier)
            ->first();
    }

    private function resolveEmployee(Request $request, bool $optionalSelf = false): ?Employee
    {
        return $this->attendance->currentEmployee();
    }

    private function isOther(Request $request, Employee $employee): bool
    {
        $self = $this->attendance->currentEmployee();
        return ! $self || $self->id !== $employee->id;
    }

    private function canManage(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }
        if (! $this->settings->enabled('allow_admin_manual', $user->tenant_id)) {
            return false;
        }
        return $user->can('attendance.manual') || $user->can('attendance.edit');
    }
}
