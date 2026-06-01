<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HRAPIController extends AppBaseController
{
    // ---- Employees ----
    public function employees(Request $request): JsonResponse
    {
        $query = Employee::query()->with('user:id,first_name,last_name,email');
        if ($request->filled('search')) {
            $s = trim((string) $request->get('search'));
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('cnic', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }
        return $this->sendResponse(
            $query->orderBy('first_name')->paginate(getPageSize($request)),
            'Employees retrieved successfully.'
        );
    }

    public function storeEmployee(Request $request): JsonResponse
    {
        $input = $this->validateEmployee($request);
        return $this->sendResponse(Employee::create($input), 'Employee created successfully.');
    }

    public function updateEmployee(Request $request, Employee $employee): JsonResponse
    {
        $input = $this->validateEmployee($request, $employee->id);
        $employee->update($input);
        return $this->sendResponse($employee->refresh(), 'Employee updated successfully.');
    }

    public function destroyEmployee(Employee $employee): JsonResponse
    {
        $employee->delete();
        return $this->sendSuccess('Employee deleted successfully.');
    }

    // ---- Attendance ----
    public function attendance(Request $request): JsonResponse
    {
        $query = Attendance::query()
            ->with('employee:id,first_name,last_name,employee_code,department');
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->get('employee_id'));
        }
        if ($request->filled('date')) {
            $query->whereDate('date', $request->get('date'));
        } else {
            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', $request->get('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', $request->get('end_date'));
            }
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        return $this->sendResponse(
            $query->orderByDesc('date')->paginate(getPageSize($request)),
            'Attendance retrieved successfully.'
        );
    }

    public function checkIn(Request $request, Employee $employee): JsonResponse
    {
        $today = Carbon::today();
        $row = Attendance::firstOrCreate(
            ['employee_id' => $employee->id, 'date' => $today->toDateString()],
            ['tenant_id' => $employee->tenant_id, 'status' => Attendance::STATUS_PRESENT]
        );
        if (!$row->check_in_at) {
            $row->update(['check_in_at' => now()]);
        }
        return $this->sendResponse($row->refresh(), 'Check-in recorded.');
    }

    public function checkOut(Request $request, Employee $employee): JsonResponse
    {
        $today = Carbon::today();
        $row = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();
        if (!$row) {
            return $this->sendError('No check-in record for today.');
        }
        $row->check_out_at = now();
        if ($row->check_in_at) {
            $row->hours_worked = round(
                Carbon::parse($row->check_out_at)->floatDiffInHours(Carbon::parse($row->check_in_at)),
                2
            );
        }
        $row->save();
        return $this->sendResponse($row->refresh(), 'Check-out recorded.');
    }

    public function updateAttendance(Request $request, Attendance $attendance): JsonResponse
    {
        $input = $request->validate([
            'status' => ['nullable', Rule::in([
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_ABSENT,
                Attendance::STATUS_LEAVE,
                Attendance::STATUS_HALF_DAY,
                Attendance::STATUS_LATE,
            ])],
            'check_in_at' => 'nullable|date',
            'check_out_at' => 'nullable|date',
            'note' => 'nullable|string|max:1000',
        ]);
        $attendance->update($input);
        return $this->sendResponse($attendance->refresh(), 'Attendance updated.');
    }

    private function validateEmployee(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'store_id' => 'nullable|exists:stores,id',
            'shop_id' => 'nullable|exists:shops,id',
            'employee_code' => 'nullable|string|max:60',
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'cnic' => 'nullable|string|max:30',
            'designation' => 'nullable|string|max:120',
            'department' => 'nullable|string|max:120',
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time', 'contract'])],
            'hired_at' => 'nullable|date',
            'terminated_at' => 'nullable|date',
            'salary' => 'nullable|numeric|min:0',
            'salary_cycle' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
            'address' => 'nullable|string|max:500',
            'status' => 'nullable|boolean',
        ]);
    }
}
