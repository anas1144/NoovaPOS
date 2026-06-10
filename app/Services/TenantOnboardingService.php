<?php

namespace App\Services;

use App\Models\MultiTenant;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\UserStore;
use App\Repositories\StoreRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Database\Models\Domain;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class TenantOnboardingService
{
    public function __construct(
        private readonly StoreRepository $storeRepository,
        private readonly TenantSubscriptionService $tenantSubscriptionService
    )
    {
    }

    public function onboard(array $input): array
    {
        return DB::transaction(function () use ($input) {
            $domain = $this->storeRepository->normalizeDomain($input['domain'] ?? $input['subdomain']);

            if (Domain::where('domain', $domain)->exists()) {
                throw new UnprocessableEntityHttpException('Domain is already assigned to another tenant.');
            }

            $store = Store::create([
                'name' => $input['business_name'],
                'status' => true,
                'is_default' => true,
            ]);

            $tenant = MultiTenant::create(['store_id' => $store->id]);
            $store->update(['tenant_id' => $tenant->id]);

            $this->storeRepository->createDomain($tenant->id, $domain);
            $this->storeRepository->storeDefaultSettings($tenant->id, $input['source_tenant_id'] ?? null);
            $this->tenantSubscriptionService->createDefaultSubscription($tenant);

            $owner = User::create([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'] ?? '',
                'email' => $input['email'],
                'phone' => $input['phone'] ?? null,
                'password' => Hash::make($input['password']),
                'tenant_id' => $tenant->id,
                'country' => $input['country'] ?? null,
            ]);

            $ownerRole = Role::firstOrCreate([
                'name' => Role::TENANT_OWNER,
                'guard_name' => 'web',
            ], [
                'display_name' => 'Tenant Owner',
            ]);

            $owner->assignRole($ownerRole);

            UserStore::create([
                'user_id' => $owner->id,
                'store_id' => $store->id,
            ]);

            return compact('domain', 'tenant', 'store', 'owner');
        });
    }
}
