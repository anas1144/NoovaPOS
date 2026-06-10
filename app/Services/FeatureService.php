<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\StoreFeature;
use App\Support\FeatureCatalog;

/**
 * Resolves feature availability.
 *
 * Effective = GLOBAL (super admin) AND STORE (tenant admin). Both default to ON
 * unless explicitly disabled.
 */
class FeatureService
{
    public function globalEnabled(string $key): bool
    {
        $v = PlatformSetting::get('feature.' . $key);
        return $v === null ? true : ((string) $v === '1');
    }

    public function setGlobal(string $key, bool $enabled): void
    {
        PlatformSetting::set('feature.' . $key, $enabled ? '1' : '0');
    }

    public function storeFlag(?int $storeId, string $key): bool
    {
        if (! $storeId) {
            return true;
        }
        $row = StoreFeature::withoutGlobalScope('tenant')
            ->where('store_id', $storeId)
            ->where('feature_key', $key)
            ->first();

        return $row ? (bool) $row->enabled : true;
    }

    public function setStore(int $storeId, string $key, bool $enabled, $tenantId = null): void
    {
        StoreFeature::withoutGlobalScope('tenant')->updateOrCreate(
            ['store_id' => $storeId, 'feature_key' => $key],
            ['enabled' => $enabled, 'tenant_id' => $tenantId]
        );
    }

    /**
     * Effective availability for a store.
     */
    public function enabled(?int $storeId, string $key): bool
    {
        return $this->globalEnabled($key) && $this->storeFlag($storeId, $key);
    }

    /**
     * Catalog with global on/off — for the super admin.
     */
    public function globalStates(): array
    {
        return array_map(function ($f) {
            $f['global_enabled'] = $this->globalEnabled($f['key']);
            return $f;
        }, FeatureCatalog::all());
    }

    /**
     * Catalog with store + global + effective state — for the tenant admin.
     */
    public function storeStates(?int $storeId): array
    {
        return array_map(function ($f) use ($storeId) {
            $global = $this->globalEnabled($f['key']);
            $store = $this->storeFlag($storeId, $f['key']);
            $f['global_enabled'] = $global;     // if false, tenant can't enable
            $f['store_enabled'] = $store;
            $f['effective'] = $global && $store;
            return $f;
        }, FeatureCatalog::all());
    }
}
