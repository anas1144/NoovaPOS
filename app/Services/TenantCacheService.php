<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Per-tenant Redis caching layer.
 *
 * - Uses tagged caches so a single tenant's cache can be flushed in one
 *   call (`forgetTenant`) without affecting other tenants.
 * - Falls back to plain (untagged) cache when the underlying driver
 *   doesn't support tags (e.g. file/database) so it works in any env.
 */
class TenantCacheService
{
    private const TTL_SHORT = 60;        // 1 minute
    private const TTL_MEDIUM = 5 * 60;   // 5 minutes
    private const TTL_LONG = 60 * 60;    // 1 hour

    /**
     * Build a tagged cache repository for the given tenant.
     */
    public function store(?string $tenantId = null)
    {
        $tenantId = $tenantId ?? currentTenantId() ?? 'global';
        $tag = 'tenant:' . $tenantId;

        try {
            return Cache::tags([$tag]);
        } catch (\BadMethodCallException $e) {
            // tags not supported by the active driver — return default store
            return Cache::store();
        }
    }

    /**
     * Generic remember helper that scopes by tenant.
     */
    public function remember(string $key, int $ttl, callable $callback, ?string $tenantId = null)
    {
        return $this->store($tenantId)->remember(
            $this->qualifyKey($key, $tenantId),
            $ttl,
            $callback
        );
    }

    public function forget(string $key, ?string $tenantId = null): void
    {
        $this->store($tenantId)->forget($this->qualifyKey($key, $tenantId));
    }

    /**
     * Flush every cache entry for a tenant in one call (tagged drivers only).
     */
    public function forgetTenant(?string $tenantId = null): void
    {
        $tenantId = $tenantId ?? currentTenantId() ?? 'global';
        try {
            Cache::tags(['tenant:' . $tenantId])->flush();
        } catch (\BadMethodCallException $e) {
            // no-op on file/database driver
        }
    }

    // ------------------------------------------------------------------
    // Cold-path helpers
    // ------------------------------------------------------------------

    public function settings(?string $tenantId = null): array
    {
        return $this->remember('settings.all', self::TTL_LONG, function () {
            return Setting::query()->pluck('value', 'name')->toArray();
        }, $tenantId);
    }

    public function permissionsForUser(int $userId, ?string $tenantId = null): array
    {
        return $this->remember(
            "perms.user.$userId",
            self::TTL_MEDIUM,
            fn() => Permission::query()
                ->whereHas('roles.users', fn($q) => $q->where('users.id', $userId))
                ->pluck('name')
                ->unique()
                ->values()
                ->all(),
            $tenantId
        );
    }

    public function productCount(?string $tenantId = null): int
    {
        return (int) $this->remember(
            'products.count',
            self::TTL_SHORT,
            fn() => \App\Models\Product::query()->count(),
            $tenantId
        );
    }

    public function customerCount(?string $tenantId = null): int
    {
        return (int) $this->remember(
            'customers.count',
            self::TTL_SHORT,
            fn() => \App\Models\Customer::query()->count(),
            $tenantId
        );
    }

    // ------------------------------------------------------------------

    private function qualifyKey(string $key, ?string $tenantId): string
    {
        $tid = $tenantId ?? currentTenantId() ?? 'global';
        return 't:' . $tid . ':' . $key;
    }
}
