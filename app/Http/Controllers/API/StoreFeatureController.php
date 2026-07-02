<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\Store;
use App\Services\FeatureService;
use App\Support\FeatureCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tenant-admin per-store feature toggles.
 */
class StoreFeatureController extends AppBaseController
{
    public function __construct(private readonly FeatureService $features)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $storeId = (int) $request->get('store_id');
        if (! $storeId) {
            return $this->sendError('store_id is required.', 422);
        }

        return $this->sendResponse(
            $this->features->storeStates($storeId),
            'Store features retrieved.'
        );
    }

    /**
     * Effective feature flags for the current user's active store — a simple
     * { key: bool } map any UI can gate on.
     */
    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();
        $storeId = $user?->active_store_id;

        // Fall back to the tenant's default store.
        if (! $storeId && $user?->tenant_id) {
            $storeId = \App\Models\Store::query()
                ->where('tenant_id', $user->tenant_id)
                ->orderByDesc('is_default')->value('id');
        }

        $map = [];
        foreach ($this->features->storeStates($storeId ? (int) $storeId : null) as $f) {
            $map[$f['key']] = (bool) ($f['effective'] ?? false);
        }

        // Expose the active store's business type so the POS UI can gate
        // shop-type-specific actions (e.g. Add Deal / Send to Kitchen are
        // restaurant-only) reliably from the server.
        $map['shop_type'] = $storeId
            ? (Store::query()->whereKey($storeId)->value('shop_type') ?: 'retail')
            : 'retail';

        return $this->sendResponse($map, 'Active store features retrieved.');
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_id'           => 'required|integer',
            'features'           => 'required|array',
            'features.*.key'     => 'required|string',
            'features.*.enabled' => 'required|boolean',
        ]);

        // Ensure the store belongs to the current tenant.
        $tenantId = $request->user()?->tenant_id;
        $store = Store::query()->where('id', $data['store_id'])->first();
        if (! $store || ($tenantId && (string) $store->tenant_id !== (string) $tenantId)) {
            return $this->sendError('Store not found for this tenant.', 422);
        }

        foreach ($data['features'] as $f) {
            if (FeatureCatalog::isValid($f['key'])) {
                $this->features->setStore((int) $data['store_id'], $f['key'], (bool) $f['enabled'], $tenantId);
            }
        }

        return $this->sendResponse(
            $this->features->storeStates((int) $data['store_id']),
            'Store features updated.'
        );
    }
}
