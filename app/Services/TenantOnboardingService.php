<?php

namespace App\Services;

use App\Models\MultiTenant;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Store;
use App\Models\Subscription;
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

            // Resolve the selected plan(s). A tenant may buy several — one per
            // shop type — each provisioning a store. Accept plan_ids[] (multi)
            // or a single plan_id; fall back to the shop_type's plan or starter.
            $planIds = $input['plan_ids']
                ?? (! empty($input['plan_id']) ? [$input['plan_id']] : []);

            $plans = collect($planIds)
                ->map(fn ($id) => Plan::query()->where('id', $id)->where('status', true)->first())
                ->filter()
                ->values();

            if ($plans->isEmpty()) {
                $shopType = $input['shop_type'] ?? 'retail';
                $fallback = Plan::query()->where('shop_type', $shopType)->where('status', true)->first()
                    ?: Plan::query()->where('slug', 'type-retail')->where('status', true)->first();
                if ($fallback) {
                    $plans = collect([$fallback]);
                }
            }

            $firstPlan = $plans->first();
            $firstShopType = $firstPlan?->shop_type ?: ($input['shop_type'] ?? 'retail');

            // Default store (named after the business) from the first plan/type.
            $store = Store::create([
                'name' => $input['business_name'],
                'status' => true,
                'is_default' => true,
                'shop_type' => $firstShopType,
            ]);

            $tenant = MultiTenant::create(['store_id' => $store->id]);
            $store->update(['tenant_id' => $tenant->id]);

            $this->storeRepository->createDomain($tenant->id, $domain);
            $this->storeRepository->storeDefaultSettings($tenant->id, $input['source_tenant_id'] ?? null);

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

            // First plan → subscription tied to the default store.
            $stores = [$store];
            if ($firstPlan) {
                $startsAt = now();
                Subscription::create([
                    'tenant_id'     => $tenant->id,
                    'plan_id'       => $firstPlan->id,
                    'shop_type'     => $firstPlan->shop_type,
                    'store_id'      => $store->id,
                    'status'        => $firstPlan->trial_days > 0 ? Subscription::STATUS_TRIALING : Subscription::STATUS_ACTIVE,
                    'starts_at'     => $startsAt,
                    'trial_ends_at' => $firstPlan->trial_days > 0 ? $startsAt->copy()->addDays($firstPlan->trial_days) : null,
                ]);
            } else {
                $this->tenantSubscriptionService->createDefaultSubscription($tenant);
            }

            // Additional plans → each gets its own store + subscription.
            foreach ($plans->slice(1) as $plan) {
                $res = $this->tenantSubscriptionService->subscribeToPlan($tenant, $plan, $owner, false);
                $stores[] = $res['store'];
            }

            return compact('domain', 'tenant', 'store', 'owner') + ['stores' => $stores];
        });
    }
}
