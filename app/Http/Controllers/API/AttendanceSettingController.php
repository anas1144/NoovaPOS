<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Services\AttendanceSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Configuration → Attendance. Tenant-admin reads/writes the attendance toggles
 * (general, methods, scanner, security).
 */
class AttendanceSettingController extends AppBaseController
{
    public function __construct(private readonly AttendanceSettingService $settings)
    {
    }

    public function show(Request $request): JsonResponse
    {
        return $this->sendResponse([
            'settings' => $this->settings->all($request->user()?->tenant_id),
            'defaults' => $this->settings->defaults(),
        ], 'Attendance settings retrieved.');
    }

    public function update(Request $request): JsonResponse
    {
        if (! ($request->user()?->can('attendance.settings.update'))) {
            return $this->sendError('You are not allowed to change attendance settings.', 403);
        }

        // Accept any known boolean keys; the service filters to its defaults.
        $values = $request->input('settings', $request->all());

        $saved = $this->settings->update(is_array($values) ? $values : [], $request->user()?->tenant_id);

        return $this->sendResponse(['settings' => $saved], 'Attendance settings saved.');
    }
}
