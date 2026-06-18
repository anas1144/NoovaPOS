<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\Customer;
use App\Models\DeviceToken;
use App\Models\Product;
use App\Services\AttendanceSettingService;
use App\Services\FeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * App-facing API (mobile Flutter + Electron desktop): identity, an offline
 * first-load bootstrap, and push-token registration. Auth tokens are issued by
 * the existing /api/login (Sanctum); offline sales upload via the existing
 * /api/offline-sync/batches.
 */
class AppApiController extends AppBaseController
{
    public function __construct(private readonly FeatureService $features)
    {
    }

    /** Identity payload for the app after login. */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->sendError('Unauthenticated.', 401);
        }

        $storeId = $user->active_store_id;
        $featureMap = [];
        foreach ($this->features->storeStates($storeId ? (int) $storeId : null) as $f) {
            $featureMap[$f['key']] = (bool) ($f['effective'] ?? false);
        }

        return $this->sendResponse([
            'user' => [
                'id'              => $user->id,
                'name'            => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'email'           => $user->email,
                'tenant_id'       => $user->tenant_id,
                'country'         => $user->country ?? null,
                'active_store_id' => $user->active_store_id ?? null,
                'roles'           => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [],
            ],
            'features'    => $featureMap,
            'permissions' => method_exists($user, 'getAllPermissions')
                ? $user->getAllPermissions()->pluck('name')
                : [],
        ], 'Identity retrieved.');
    }

    /**
     * Offline first-load bundle: products, customers, price tiers and settings
     * the app caches locally. Keep it lightweight (apps page detail on demand).
     */
    public function bootstrap(Request $request): JsonResponse
    {
        $products = Product::query()
            ->limit((int) $request->get('limit', 5000))
            ->get(['id', 'name', 'code', 'product_price', 'product_cost'])
            ->map(fn ($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'code'  => $p->code,
                'price' => (float) $p->product_price,
                'cost'  => (float) $p->product_cost,
            ]);

        $hasTier = Schema::hasColumn('customers', 'price_tier');
        $customers = Customer::query()
            ->limit((int) $request->get('customer_limit', 5000))
            ->get()
            ->map(fn ($c) => [
                'id'         => $c->id,
                'name'       => $c->name,
                'phone'      => $c->phone ?? null,
                'price_tier' => $hasTier ? ($c->price_tier ?? null) : null,
            ]);

        $tiers = Schema::hasTable('price_tiers')
            ? \DB::table('price_tiers')->get(['id', 'key', 'label'])
            : collect();

        return $this->sendResponse([
            'synced_at'  => now()->toIso8601String(),
            'products'   => $products,
            'customers'  => $customers,
            'price_tiers' => $tiers,
            'settings'   => [
                'currency' => config('app.currency', 'PKR'),
            ],
        ], 'Bootstrap bundle retrieved.');
    }

    // ── push tokens ──────────────────────────────────────────────────────────

    public function registerDevice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'       => 'required|string|max:512',
            'platform'    => 'nullable|in:android,ios,desktop,web',
            'device_name' => 'nullable|string|max:120',
        ]);

        $token = DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id'      => $request->user()->id,
                'platform'     => $data['platform'] ?? 'android',
                'device_name'  => $data['device_name'] ?? null,
                'last_seen_at' => now(),
            ]
        );

        return $this->sendResponse(['id' => $token->id], 'Device registered for push.');
    }

    public function unregisterDevice(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => 'required|string']);
        DeviceToken::query()->where('token', $data['token'])->delete();
        return $this->sendSuccess('Device unregistered.');
    }
}
