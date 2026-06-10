<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\Employee;
use App\Models\EmployeeBiometric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Employee biometric enrolment.
 *
 *  - Face: stores face-api.js 128-float descriptors (one per sample). Matching
 *    happens client-side at the kiosk against `faceData()`.
 *  - Fingerprint: future-ready — stores camera sample references / a profile
 *    flag now; hardware/WebAuthn matching can be added later.
 *
 * No raw images are needed for matching, so only the numeric descriptors are
 * persisted (small, non-reversible).
 */
class EmployeeBiometricController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $rows = EmployeeBiometric::query()
            ->with('employee:id,first_name,last_name,employee_code')
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->get('employee_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->get('type')))
            ->latest('id')
            ->get();

        return $this->sendResponse($rows, 'Biometric enrolments retrieved.');
    }

    /**
     * All enrolled face descriptors for the tenant — the kiosk downloads this
     * once to match a live face locally. Returns only id/name/descriptors.
     */
    public function faceData(Request $request): JsonResponse
    {
        $rows = EmployeeBiometric::query()
            ->where('type', EmployeeBiometric::TYPE_FACE)
            ->where('status', 'enrolled')
            ->with('employee:id,first_name,last_name,employee_code')
            ->get()
            ->map(fn ($b) => [
                'employee_id'   => $b->employee_id,
                'name'          => $b->employee?->full_name,
                'employee_code' => $b->employee?->employee_code,
                'descriptors'   => $b->descriptors ?? [],
            ]);

        return $this->sendResponse($rows, 'Face data retrieved.');
    }

    public function enrollFace(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id'   => 'required|integer',
            'descriptors'   => 'required|array|min:1',
            'descriptors.*' => 'array|min:1', // each a 128-float vector
        ]);

        $employee = Employee::query()->find($data['employee_id']);
        if (! $employee) {
            return $this->sendError('Employee not found.', 404);
        }

        $bio = EmployeeBiometric::updateOrCreate(
            ['employee_id' => $employee->id, 'type' => EmployeeBiometric::TYPE_FACE],
            [
                'descriptors'        => array_values($data['descriptors']),
                'samples_count'      => count($data['descriptors']),
                'status'             => 'enrolled',
                'last_registered_at' => now(),
            ]
        );

        $this->ensureAttendanceCode($employee);

        return $this->sendResponse($bio, 'Face enrolled successfully.');
    }

    /**
     * Future-ready fingerprint enrolment: record that a camera profile exists.
     * `samples` is an optional count of captured images.
     */
    public function enrollFingerprint(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|integer',
            'samples'     => 'nullable|integer|min:1|max:20',
        ]);

        $employee = Employee::query()->find($data['employee_id']);
        if (! $employee) {
            return $this->sendError('Employee not found.', 404);
        }

        $bio = EmployeeBiometric::updateOrCreate(
            ['employee_id' => $employee->id, 'type' => EmployeeBiometric::TYPE_FINGERPRINT],
            [
                'samples_count'      => (int) ($data['samples'] ?? 1),
                'status'             => 'enrolled',
                'last_registered_at' => now(),
            ]
        );

        $this->ensureAttendanceCode($employee);

        return $this->sendResponse($bio, 'Fingerprint profile saved (future-ready).');
    }

    public function destroy(Request $request, EmployeeBiometric $biometric): JsonResponse
    {
        $biometric->delete();
        return $this->sendSuccess('Biometric enrolment removed.');
    }

    /** Generate a stable scan code for barcode/QR cards if missing. */
    private function ensureAttendanceCode(Employee $employee): void
    {
        if (empty($employee->attendance_code)) {
            $employee->attendance_code = 'EMP-' . strtoupper(Str::random(8));
            $employee->save();
        }
    }
}
