<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\FbrBusiness;
use App\Models\FbrSandboxScenario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * FBR DI Sandbox Testing Center — lists scenarios (SN001…SN028) and runs a test
 * for one against the FBR sandbox using a business's sandbox token.
 */
class FbrSandboxController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $rows = FbrSandboxScenario::query()->orderBy('code')->get();
        return $this->sendResponse($rows, 'Sandbox scenarios retrieved.');
    }

    public function run(Request $request, $id): JsonResponse
    {
        if (! $request->user()?->can('fbr_invoice_testing')) {
            return $this->sendError('You do not have permission to run sandbox tests.', 403);
        }

        $scenario = FbrSandboxScenario::query()->findOrFail($id);

        // Use the chosen business's sandbox token (or the first available).
        $business = $request->filled('fbr_business_id')
            ? FbrBusiness::query()->find($request->get('fbr_business_id'))
            : FbrBusiness::query()->whereNotNull('sandbox_token')->first();

        if (! $business || ! $business->sandbox_token) {
            $scenario->update([
                'status'      => 'fail',
                'last_result' => 'No sandbox token configured on any business.',
                'last_run_at' => now(),
            ]);
            return $this->sendError('Configure a business sandbox token first.', 422);
        }

        $url = env('FBR_DI_SANDBOX_URL', 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata_sb');
        $payload = is_array($scenario->payload) ? $scenario->payload : [];

        try {
            $response = Http::withToken($business->sandbox_token)->timeout(30)->acceptJson()->post($url, $payload);
            $json = $response->json() ?? [];
            $vr = $json['validationResponse'] ?? $json['ValidationResponse'] ?? [];
            $pass = (string) ($vr['statusCode'] ?? '') === '00' || ! empty($json['invoiceNumber']);

            $scenario->update([
                'status'      => $pass ? 'pass' : 'fail',
                'last_result' => $pass ? 'Accepted by sandbox' : ($vr['error'] ?? 'Rejected'),
                'response'    => json_encode($json),
                'last_run_at' => now(),
            ]);

            return $this->sendResponse($scenario->fresh(), 'Scenario executed.');
        } catch (\Throwable $e) {
            $scenario->update([
                'status'      => 'fail',
                'last_result' => $e->getMessage(),
                'last_run_at' => now(),
            ]);
            return $this->sendError('Sandbox call failed: ' . $e->getMessage(), 422);
        }
    }
}
