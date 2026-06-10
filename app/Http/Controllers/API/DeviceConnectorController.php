<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\DeviceConnector;
use App\Services\DeviceConnectorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manage no-code device connectors (CRUD + a test call). Listing for the kiosk
 * returns only client-run connectors with secrets stripped.
 */
class DeviceConnectorController extends AppBaseController
{
    public function __construct(private readonly DeviceConnectorService $devices)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $rows = DeviceConnector::query()
            ->when($request->filled('modality'), fn ($q) => $q->where('modality', $request->get('modality')))
            ->orderBy('name')->get();

        return $this->sendResponse($rows, 'Device connectors retrieved.');
    }

    /** Enabled client-run connectors for the kiosk (no secrets). */
    public function forKiosk(Request $request): JsonResponse
    {
        $rows = DeviceConnector::query()
            ->where('status', true)
            ->where('run_on', DeviceConnector::RUN_CLIENT)
            ->get()
            ->map(fn ($c) => $c->toClientArray());

        return $this->sendResponse($rows, 'Kiosk device connectors retrieved.');
    }

    public function store(Request $request): JsonResponse
    {
        return $this->persist(new DeviceConnector(), $request, 'Device connector created.');
    }

    public function update(Request $request, DeviceConnector $deviceConnector): JsonResponse
    {
        return $this->persist($deviceConnector, $request, 'Device connector updated.');
    }

    public function destroy(DeviceConnector $deviceConnector): JsonResponse
    {
        $deviceConnector->delete();
        return $this->sendSuccess('Device connector removed.');
    }

    /** Fire a test verify with caller-supplied sample variables. */
    public function test(Request $request, DeviceConnector $deviceConnector): JsonResponse
    {
        $vars = (array) $request->input('vars', []);
        $result = $this->devices->verify($deviceConnector, $vars);
        return $this->sendResponse($result, 'Connector test executed.');
    }

    private function persist(DeviceConnector $connector, Request $request, string $message): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:120',
            'modality'    => 'required|in:face,fingerprint,card,rfid,nfc',
            'run_on'      => 'required|in:server,client',
            'base_url'    => 'nullable|string|max:255',
            'verify_path' => 'nullable|string|max:255',
            'enroll_path' => 'nullable|string|max:255',
            'http_method' => 'nullable|in:GET,POST,PUT,PATCH,DELETE',
            'auth_type'   => 'nullable|in:none,api_key,bearer,basic',
            'auth_header' => 'nullable|string|max:60',
            'auth_token'  => 'nullable|string|max:2000',
            'headers'                 => 'nullable|array',
            'request_template'        => 'nullable|array',
            'response_success_path'   => 'nullable|string|max:120',
            'success_value'           => 'nullable|string|max:60',
            'response_employee_path'  => 'nullable|string|max:120',
            'timeout'     => 'nullable|integer|min:1|max:120',
            'status'      => 'nullable|boolean',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $connector->fill($data)->save();

        return $this->sendResponse($connector->fresh(), $message);
    }
}
