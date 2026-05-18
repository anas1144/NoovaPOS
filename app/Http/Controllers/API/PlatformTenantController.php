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

        $query = MultiTenant::query()
            ->leftJoin('stores', 'stores.tenant_id', '=', 'tenants.id')
            ->leftJoin('domains', 'domains.tenant_id', '=', 'tenants.id')
            ->select([
                'tenants.id',
                'tenants.store_id',
                'tenants.created_at',
                'stores.name as store_name',
                'stores.status as store_status',
                'domains.domain as tenant_domain',
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('tenants.id', 'like', "%{$search}%")
                    ->orWhere('stores.name', 'like', "%{$search}%")
                    ->orWhere('domains.domain', 'like', "%{$search}%");
            });
        }

        $result = $query->orderByDesc('tenants.created_at')->paginate($perPage);

        $ownerEmails = User::withoutGlobalScope('tenant')
            ->whereIn('tenant_id', $result->pluck('id'))
            ->whereHas('roles', fn($q) => $q->where('name', Role::TENANT_OWNER))
            ->select(['tenant_id', 'email'])
            ->get()
            ->keyBy('tenant_id');

        $subscriptions = Subscription::query()
            ->with('plan:id,name')
            ->whereIn('tenant_id', $result->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->unique('tenant_id')
            ->keyBy('tenant_id');

        $data = $result->through(function ($row) use ($ownerEmails, $subscriptions) {
            $subscription = $subscriptions->get($row->id);

            return [
                'id' => $row->id,
                'store_id' => $row->store_id,
                'store_name' => $row->store_name,
                'domain' => $row->tenant_domain,
                'status' => (bool) $row->store_status,
                'subscription_status' => optional($subscription)->status,
                'plan_name' => optional(optional($subscription)->plan)->name,
                'owner_email' => optional($ownerEmails->get($row->id))->email,
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
