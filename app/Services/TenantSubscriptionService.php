<?php

namespace App\Services;

use App\Models\MultiTenant;
use App\Models\Plan;
use App\Models\POSRegister;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class TenantSubscriptionService
{
    public function latestSubscription(?string $tenantId): ?Subscription
    {
        if (!$tenantId) {
            return null;
        }

        return Subscription::query()
            ->with('plan')
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first();
    }

    public function assertTenantCanAccess(?string $tenantId): void
    {
        if (!$tenantId) {
            return;
        }

        $subscription = $this->latestSubscription($tenantId);
        if (!$subscription) {
            return;
        }

        if (in_array($subscription->status, [
            Subscription::STATUS_CANCELED,
            Subscription::STATUS_SUSPENDED,
            Subscription::STATUS_EXPIRED,
        ], true)) {
            throw new AccessDeniedHttpException('Tenant subscription is not active.');
        }

        if ($subscription->ends_at && $subscription->ends_at->isPast()) {
            throw new AccessDeniedHttpException('Tenant subscription has ended.');
        }

        if ($subscription->status === Subscription::STATUS_TRIALING && $subscription->trial_ends_at && $subscription->trial_ends_at->isPast()) {
            throw new AccessDeniedHttpException('Tenant trial has ended.');
        }
    }

    public function assertWithinLimit(?string $tenantId, string $resource, int $increment = 1): void
    {
        if (!$tenantId) {
            return;
        }

        $subscription = $this->latestSubscription($tenantId);
        $plan = $subscription?->plan;

        if (!$plan) {
            return;
        }

        $limit = $this->limitFor($plan, $resource);
        if ($limit === null) {
            return;
        }

        $current = $this->usageFor($tenantId, $resource);
        if (($current + $increment) > $limit) {
            throw new UnprocessableEntityHttpException("Plan limit reached for {$resource}.");
        }
    }

    public function usage(string $tenantId): array
    {
        $subscription = $this->latestSubscription($tenantId);
        $plan = $subscription?->plan;

        return [
            'subscription' => $subscription,
            'limits' => [
                'stores' => $plan?->max_stores,
                'shops' => $plan?->max_shops,
                'registers' => $plan?->max_registers,
                'users' => $plan?->max_users,
                'products' => $plan?->max_products,
            ],
            'usage' => [
                'stores' => $this->usageFor($tenantId, 'stores'),
                'shops' => $this->usageFor($tenantId, 'shops'),
                'registers' => $this->usageFor($tenantId, 'registers'),
                'users' => $this->usageFor($tenantId, 'users'),
                'products' => $this->usageFor($tenantId, 'products'),
            ],
        ];
    }

    public function createDefaultSubscription(MultiTenant $tenant): ?Subscription
    {
        $plan = Plan::query()
            ->where('slug', 'starter')
            ->where('status', true)
            ->first();

        if (!$plan) {
            return null;
        }

        $startsAt = now();

        return Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => $plan->trial_days > 0 ? Subscription::STATUS_TRIALING : Subscription::STATUS_ACTIVE,
            'starts_at' => $startsAt,
            'trial_ends_at' => $plan->trial_days > 0 ? $startsAt->copy()->addDays($plan->trial_days) : null,
        ]);
    }

    private function limitFor(Plan $plan, string $resource): ?int
    {
        return match ($resource) {
            'stores' => $plan->max_stores,
            'shops' => $plan->max_shops,
            'registers' => $plan->max_registers,
            'users' => $plan->max_users,
            'products' => $plan->max_products,
            default => null,
        };
    }

    private function usageFor(string $tenantId, string $resource): int
    {
        return match ($resource) {
            'stores' => Store::query()->where('tenant_id', $tenantId)->count(),
            'shops' => Shop::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->count(),
            'registers' => POSRegister::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->count(),
            'users' => User::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->count(),
            'products' => Product::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->count(),
            default => 0,
        };
    }
}
