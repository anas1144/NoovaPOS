<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\RegisterTenantRequest;
use App\Models\MultiTenant;
use App\Services\TenantOnboardingService;
use Exception;
use Illuminate\Http\JsonResponse;

class TenantRegistrationController extends AppBaseController
{
    public function __construct(private readonly TenantOnboardingService $tenantOnboardingService)
    {
    }

    public function register(RegisterTenantRequest $request): JsonResponse
    {
        $input = $request->validated();

        try {
            ['domain' => $domain, 'tenant' => $tenant, 'store' => $store, 'owner' => $owner] =
                $this->tenantOnboardingService->onboard($input);

            $token = $owner->createToken('token')->plainTextToken;

            return response()->json([
                'data' => [
                    'token' => $token,
                    'tenant' => [
                        'id' => $tenant->id,
                        'domain' => $domain,
                    ],
                    'store' => [
                        'id' => $store->id,
                        'name' => $store->name,
                        'tenant_id' => $store->tenant_id,
                    ],
                    'user' => $owner,
                    'roles' => $owner->roles->pluck('name')->first(),
                ],
                'message' => 'Tenant registered successfully.',
            ]);
        } catch (Exception $exception) {
            return $this->sendError($exception->getMessage());
        }
    }
}
