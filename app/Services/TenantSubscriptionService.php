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

        // Separate-DB plans may grant a different user allowance.
        if ($resource === 'users') {
            $limit = $this->effectiveUserLimit($plan, $tenantId) ?? $limit;
        }

        // Add the tenant's purchased add-on allowance to the plan limit.
        $limit += $this->extrasFor($tenantId)[$resource] ?? 0;

        $current = $this->usageFor($tenantId, $resource);
        if (($current + $increment) > $limit) {
            throw new UnprocessableEntityHttpException("Plan limit reached for {$resource}.");
        }
    }

    public function usage(string $tenantId): array
    {
        $subscription = $this->latestSubscription($tenantId);
        $plan = $subscription?->plan;
        $extras = $this->extrasFor($tenantId);

        $withExtra = fn ($limit, $key) => $limit === null ? null : $limit + ($extras[$key] ?? 0);

        return [
            'subscription' => $subscription,
            'limits' => [
                'stores' => $plan?->max_stores,
                'shops' => $withExtra($plan?->max_shops, 'shops'),
                'registers' => $plan?->max_registers,
                'users' => $withExtra($this->effectiveUserLimit($plan, $tenantId), 'users'),
                'products' => $withExtra($plan?->max_products, 'products'),
            ],
            'extras' => $extras,
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

    /**
     * Price for a plan over the given cycle (monthly|yearly), per single period.
     */
    public function priceFor(Plan $plan, string $cycle): float
    {
        if ($cycle === 'yearly') {
            return (float) ($plan->price_yearly ?? ($plan->price ? $plan->price * 12 : 0));
        }

        return (float) ($plan->price ?? 0);
    }

    /**
     * Total amount for a plan over N billing periods, after any long-term
     * discount (set by the super admin) for purchases >= the threshold months.
     */
    public function amountFor(Plan $plan, string $cycle, int $periods, ?string $country = null): float
    {
        $periods = max(1, $periods);
        $gross = $this->priceFor($plan, $cycle) * $periods;
        $discountPercent = $this->discountPercentFor($cycle, $periods, $country);

        return round($gross * (1 - $discountPercent / 100), 2);
    }

    /**
     * Months covered by the purchase (yearly periods count as 12 months each).
     */
    public function totalMonths(string $cycle, int $periods): int
    {
        return $cycle === 'yearly' ? $periods * 12 : $periods;
    }

    /**
     * Long-term discount percentage that applies to this purchase, or 0.
     */
    public function discountPercentFor(string $cycle, int $periods, ?string $country = null): float
    {
        $minMonths = (int) \App\Models\PlatformSetting::getFloatForCountry('long_term_discount_min_months', $country, 12);
        $percent = \App\Models\PlatformSetting::getFloatForCountry('long_term_discount_percent', $country, 0);

        return $this->totalMonths($cycle, $periods) >= max(1, $minMonths) ? $percent : 0.0;
    }

    /**
     * FBR (Pakistan) price for the given cycle, country-aware.
     */
    public function fbrPrice(string $cycle, ?string $country = null): float
    {
        $key = $cycle === 'yearly' ? 'fbr_yearly_price' : 'fbr_monthly_price';
        return \App\Models\PlatformSetting::getFloatForCountry($key, $country, 0);
    }

    /**
     * Activate (or extend) FBR for a tenant for `periods` cycles.
     */
    public function applyFbr(string $tenantId, string $cycle, int $periods): \App\Models\TenantAddon
    {
        $periods = max(1, $periods);
        $addon = \App\Models\TenantAddon::query()->firstOrNew(['tenant_id' => $tenantId]);

        $base = ($addon->fbr_expires_at && $addon->fbr_expires_at->isFuture())
            ? $addon->fbr_expires_at->copy()
            : now();

        $addon->fbr_enabled = true;
        $addon->fbr_expires_at = $cycle === 'yearly'
            ? $base->copy()->addYears($periods)
            : $base->copy()->addMonths($periods);
        $addon->save();

        return $addon;
    }

    /**
     * FBR status for a tenant (active only while not expired).
     */
    public function fbrStatus(?string $tenantId): array
    {
        $addon = $tenantId
            ? \App\Models\TenantAddon::query()->where('tenant_id', $tenantId)->first()
            : null;

        $active = $addon && $addon->fbr_enabled
            && (! $addon->fbr_expires_at || $addon->fbr_expires_at->isFuture());

        return [
            'enabled'    => (bool) $active,
            'expires_at' => $addon->fbr_expires_at ?? null,
        ];
    }

    /**
     * Activate (or renew) a tenant's subscription for `periods` billing cycles.
     * If the current subscription is still active in the future, the new period
     * is appended (stacking), otherwise it starts now.
     */
    public function activateForPeriod(string $tenantId, Plan $plan, string $cycle, int $periods): Subscription
    {
        $periods = max(1, $periods);
        $current = $this->latestSubscription($tenantId);

        $base = ($current && $current->ends_at && $current->ends_at->isFuture())
            ? $current->ends_at->copy()
            : now();

        $endsAt = $cycle === 'yearly'
            ? $base->copy()->addYears($periods)
            : $base->copy()->addMonths($periods);

        return Subscription::create([
            'tenant_id'     => $tenantId,
            'plan_id'       => $plan->id,
            'status'        => Subscription::STATUS_ACTIVE,
            'starts_at'     => now(),
            'trial_ends_at' => null,
            'renews_at'     => $endsAt,
            'ends_at'       => $endsAt,
        ]);
    }

    /**
     * Tenant's purchased add-on allowance (extra shops/users/products).
     */
    public function extrasFor(?string $tenantId): array
    {
        $addon = $tenantId
            ? \App\Models\TenantAddon::query()->where('tenant_id', $tenantId)->first()
            : null;

        return [
            'shops'    => (int) ($addon->extra_shops ?? 0),
            'users'    => (int) ($addon->extra_users ?? 0),
            'products' => (int) ($addon->extra_products ?? 0),
        ];
    }

    /**
     * Price for an add-on bundle using the super-admin-set per-unit rates.
     */
    public function addonAmount(int $shops, int $users, int $products, ?string $country = null): float
    {
        $shopRate    = \App\Models\PlatformSetting::getFloatForCountry('addon_shop_rate', $country, 0);
        $userRate    = \App\Models\PlatformSetting::getFloatForCountry('addon_user_rate', $country, 0);
        $productRate = \App\Models\PlatformSetting::getFloatForCountry('addon_product_rate', $country, 0);

        return round(
            max(0, $shops) * $shopRate
            + max(0, $users) * $userRate
            + max(0, $products) * $productRate,
            2
        );
    }

    /**
     * Add purchased add-ons to the tenant's cumulative allowance.
     */
    public function applyAddons(string $tenantId, int $shops, int $users, int $products): \App\Models\TenantAddon
    {
        $addon = \App\Models\TenantAddon::query()->firstOrNew(['tenant_id' => $tenantId]);
        $addon->extra_shops    = (int) $addon->extra_shops + max(0, $shops);
        $addon->extra_users    = (int) $addon->extra_users + max(0, $users);
        $addon->extra_products = (int) $addon->extra_products + max(0, $products);
        $addon->save();

        return $addon;
    }

    /**
     * Effective limit for a resource = plan limit (+ separate-DB override for
     * users) + confirmed add-on allowance. Returns null when unlimited.
     */
    public function effectiveLimit(?string $tenantId, string $resource): ?int
    {
        $plan = $this->latestSubscription($tenantId)?->plan;
        if (! $plan) {
            return null;
        }

        $limit = $resource === 'users'
            ? $this->effectiveUserLimit($plan, $tenantId)
            : $this->limitFor($plan, $resource);

        if ($limit === null) {
            return null;
        }

        return $limit + ($this->extrasFor($tenantId)[$resource] ?? 0);
    }

    /**
     * The product id cutoff: products with id <= cutoff are within the plan
     * limit (oldest N stay active); products with a greater id are over-limit and
     * must be hidden from POS/sale. Returns null when unlimited or within limit.
     */
    public function productCutoff(?string $tenantId): ?int
    {
        $limit = $this->effectiveLimit($tenantId, 'products');
        if ($limit === null || ! $tenantId) {
            return null;
        }

        // The id of the Nth-oldest product (N = limit). null if there are <= N.
        $cutoff = Product::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->skip(max(0, $limit - 1))
            ->take(1)
            ->value('id');

        return $cutoff; // null => within limit, no restriction
    }

    /**
     * Over-limit record ids for a resource (the NEWEST excess beyond the limit).
     * Used to disable shops/users when the tenant exceeds its allowance.
     */
    public function overLimitIds(?string $tenantId, string $resource): array
    {
        $limit = $this->effectiveLimit($tenantId, $resource);
        if ($limit === null || ! $tenantId) {
            return [];
        }

        $model = match ($resource) {
            'shops' => \App\Models\Shop::class,
            'users' => User::class,
            default => null,
        };
        if (! $model) {
            return [];
        }

        // Keep the oldest N; everything created after is over-limit.
        return $model::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->skip($limit)
            ->pluck('id')
            ->all();
    }

    /**
     * Effective user limit: a separate-DB tenant uses the plan's
     * separate_db_max_users (when set), otherwise the normal max_users.
     */
    private function effectiveUserLimit(?Plan $plan, ?string $tenantId): ?int
    {
        if (! $plan) {
            return null;
        }

        if ($plan->separate_db_max_users !== null && $tenantId) {
            $tenant = MultiTenant::find($tenantId);
            if ($tenant && method_exists($tenant, 'usesSeparateDb') && $tenant->usesSeparateDb()) {
                return (int) $plan->separate_db_max_users;
            }
        }

        return $plan->max_users;
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
            // Registers are a MONTHLY quota that resets each month — count only
            // registers opened in the current calendar month.
            'registers' => POSRegister::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'users' => User::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->count(),
            'products' => Product::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->count(),
            default => 0,
        };
    }
}
