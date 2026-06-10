<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreatePlatformTenantRequest;
use App\Models\AuditLog;
use App\Models\MultiTenant;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Store;
use App\Models\User;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\UserStore;
use App\Services\TenantOnboardingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\Models\Domain;

class PlatformTenantController extends AppBaseController
{
    public function __construct(private readonly TenantOnboardingService $tenantOnboardingService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = getPageSize($request);
        $search = trim((string) $request->get('search', ''));

        // One row per tenant. Stores are nested under each tenant rather than
        // joined 1:1 (which previously produced a duplicate tenant row per store).
        $query = MultiTenant::query()->select([
            'tenants.id',
            'tenants.store_id',
            'tenants.uses_separate_db',
            'tenants.created_at',
        ]);

        if ($search !== '') {
            $matchedTenantIds = Store::withoutGlobalScope('tenant')
                ->where('name', 'like', "%{$search}%")
                ->pluck('tenant_id');

            $matchedByDomain = Domain::query()
                ->where('domain', 'like', "%{$search}%")
                ->pluck('tenant_id');

            $query->where(function ($q) use ($search, $matchedTenantIds, $matchedByDomain) {
                $q->where('tenants.id', 'like', "%{$search}%")
                    ->orWhereIn('tenants.id', $matchedTenantIds)
                    ->orWhereIn('tenants.id', $matchedByDomain);
            });
        }

        $result = $query->orderByDesc('tenants.created_at')->paginate($perPage);
        $tenantIds = $result->pluck('id');

        // All stores grouped by tenant (for the expandable store list).
        $storesByTenant = Store::withoutGlobalScope('tenant')
            ->whereIn('tenant_id', $tenantIds)
            ->select(['id', 'tenant_id', 'name', 'status', 'is_default', 'created_at'])
            ->orderBy('id')
            ->get()
            ->groupBy('tenant_id');

        // Primary domain per tenant.
        $domainsByTenant = Domain::query()
            ->whereIn('tenant_id', $tenantIds)
            ->get()
            ->groupBy('tenant_id');

        $ownerEmails = User::withoutGlobalScope('tenant')
            ->whereIn('tenant_id', $tenantIds)
            ->whereHas('roles', fn($q) => $q->where('name', Role::TENANT_OWNER))
            ->select(['tenant_id', 'email'])
            ->get()
            ->keyBy('tenant_id');

        $subscriptions = Subscription::query()
            ->with('plan:id,name')
            ->whereIn('tenant_id', $tenantIds)
            ->orderByDesc('id')
            ->get()
            ->unique('tenant_id')
            ->keyBy('tenant_id');

        $data = $result->through(function ($row) use ($ownerEmails, $subscriptions, $storesByTenant, $domainsByTenant) {
            $subscription = $subscriptions->get($row->id);
            $stores = $storesByTenant->get($row->id, collect());
            $primaryStore = $stores->firstWhere('is_default', true) ?? $stores->first();
            $primaryDomain = optional($domainsByTenant->get($row->id, collect())->first())->domain;

            return [
                'id' => $row->id,
                'store_id' => $row->store_id,
                // Business name = the tenant's default/primary store name.
                'store_name' => optional($primaryStore)->name,
                'domain' => $primaryDomain,
                // Tenant is considered active if its primary store is active.
                'status' => (bool) optional($primaryStore)->status,
                'stores_count' => $stores->count(),
                'stores' => $stores->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'status' => (bool) $s->status,
                    'is_default' => (bool) $s->is_default,
                ])->values(),
                'subscription_status' => optional($subscription)->status,
                'plan_name' => optional(optional($subscription)->plan)->name,
                'owner_email' => optional($ownerEmails->get($row->id))->email,
                'uses_separate_db' => (bool) $row->uses_separate_db,
                'created_at' => $row->created_at,
            ];
        });

        return $this->sendResponse($data, 'Tenants retrieved successfully.');
    }

    public function store(CreatePlatformTenantRequest $request): JsonResponse
    {
        try {
            ['domain' => $domain, 'tenant' => $tenant, 'store' => $store, 'owner' => $owner] =
                $this->tenantOnboardingService->onboard($request->validated());

            return $this->sendResponse([
                'tenant_id' => $tenant->id,
                'domain' => $domain,
                'store' => [
                    'id' => $store->id,
                    'name' => $store->name,
                ],
                'owner' => [
                    'id' => $owner->id,
                    'email' => $owner->email,
                ],
            ], 'Tenant created successfully.');
        } catch (Exception $exception) {
            return $this->sendError($exception->getMessage());
        }
    }

    public function show(string $tenantId): JsonResponse
    {
        $tenant = MultiTenant::query()->findOrFail($tenantId);

        $stores = Store::query()
            ->where('tenant_id', $tenant->id)
            ->select(['id', 'name', 'status', 'is_default', 'created_at'])
            ->orderBy('id')
            ->get();

        $domains = Domain::query()
            ->where('tenant_id', $tenant->id)
            ->pluck('domain')
            ->values();

        $owner = User::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->whereHas('roles', fn($q) => $q->where('name', Role::TENANT_OWNER))
            ->select(['id', 'first_name', 'last_name', 'email', 'phone', 'status'])
            ->first();

        $storeIds = $stores->pluck('id');
        $userIds = UserStore::query()->whereIn('store_id', $storeIds)->distinct()->pluck('user_id');

        $stats = [
            'stores' => $stores->count(),
            'active_stores' => $stores->where('status', true)->count(),
            'users' => User::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
            'assigned_users' => $userIds->count(),
            'products' => Product::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
            'customers' => Customer::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
            'suppliers' => Supplier::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
            'warehouses' => Warehouse::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
            'sales' => Sale::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->count(),
            'sales_total' => (float) Sale::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->sum('grand_total'),
        ];

        $subscription = Subscription::query()
            ->with('plan:id,name,slug,price,billing_cycle')
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->first();

        return $this->sendResponse([
            'tenant' => [
                'id' => $tenant->id,
                'store_id' => $tenant->store_id,
                'created_at' => $tenant->created_at,
                'updated_at' => $tenant->updated_at,
            ],
            'owner' => $owner,
            'domains' => $domains,
            'stores' => $stores,
            'subscription' => $subscription,
            'stats' => $stats,
        ], 'Tenant retrieved successfully.');
    }

    public function grantAddons(Request $request, string $tenantId): JsonResponse
    {
        $data = $request->validate([
            'shops'    => 'nullable|integer|min:0|max:10000',
            'users'    => 'nullable|integer|min:0|max:10000',
            'products' => 'nullable|integer|min:0|max:1000000',
        ]);

        MultiTenant::query()->findOrFail($tenantId);

        app(\App\Services\TenantSubscriptionService::class)->applyAddons(
            $tenantId,
            (int) ($data['shops'] ?? 0),
            (int) ($data['users'] ?? 0),
            (int) ($data['products'] ?? 0)
        );

        return $this->sendSuccess('Add-on allowance granted to tenant.');
    }

    public function toggleSeparateDb(Request $request, string $tenantId): JsonResponse
    {
        $request->validate(['uses_separate_db' => 'required|boolean']);

        $tenant = MultiTenant::query()->findOrFail($tenantId);
        $tenant->uses_separate_db = $request->boolean('uses_separate_db');
        $tenant->save();

        AuditLog::create([
            'tenant_id' => $tenant->id,
            'actor_id' => auth()->id(),
            'event' => AuditLog::UPDATED_TENANT_STATUS,
            'auditable_type' => MultiTenant::class,
            'auditable_id' => null,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'old_values' => ['uses_separate_db' => ! $tenant->uses_separate_db],
            'new_values' => ['uses_separate_db' => $tenant->uses_separate_db],
            'created_at' => now(),
        ]);

        return $this->sendSuccess('Tenant database mode updated. Provision the database with: php artisan tenancy:create-databases');
    }

    public function changeStatus(Request $request, string $tenantId): JsonResponse
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        $tenant = MultiTenant::query()->findOrFail($tenantId);

        DB::transaction(function () use ($tenant, $request) {
            $status = (bool) $request->boolean('status');
            $oldStatus = Store::where('tenant_id', $tenant->id)->pluck('status', 'id')->toArray();

            Store::where('tenant_id', $tenant->id)->update(['status' => $status]);
            User::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->update(['status' => $status]);

            AuditLog::create([
                'tenant_id' => $tenant->id,
                'actor_id' => auth()->id(),
                'event' => AuditLog::UPDATED_TENANT_STATUS,
                'auditable_type' => MultiTenant::class,
                'auditable_id' => null,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'old_values' => ['store_statuses' => $oldStatus],
                'new_values' => ['status' => $status],
                'created_at' => now(),
            ]);
        });

        return $this->sendSuccess('Tenant status updated successfully.');
    }
}
