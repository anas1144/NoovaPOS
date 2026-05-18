<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\MultiTenant;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\TenantBackup;
use App\Models\User;
use App\Services\TenantSubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlatformSaasController extends AppBaseController
{
    public function __construct(private readonly TenantSubscriptionService $tenantSubscriptionService)
    {
    }

    public function plans(Request $request): JsonResponse
    {
        $perPage = getPageSize($request);
        $query = Plan::query();

        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $this->sendResponse($query->orderBy('price')->paginate($perPage), 'Plans retrieved successfully.');
    }

    public function dashboard(): JsonResponse
    {
        $tenantCount = MultiTenant::query()->count();
        $latestSubscriptions = Subscription::query()
            ->with('plan:id,name')
            ->orderByDesc('id')
            ->get()
            ->unique('tenant_id')
            ->values();
        $activeTenantCount = $latestSubscriptions
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
            ->count();
        $suspendedTenantCount = $latestSubscriptions
            ->whereIn('status', [Subscription::STATUS_SUSPENDED, Subscription::STATUS_CANCELED, Subscription::STATUS_EXPIRED])
            ->count();

        $subscriptionStatus = $latestSubscriptions->groupBy('status')->map->count();
        $planUsage = $latestSubscriptions
            ->filter(fn(Subscription $subscription) => $subscription->plan)
            ->groupBy(fn(Subscription $subscription) => $subscription->plan->name)
            ->map->count();

        return $this->sendResponse([
            'tenants' => [
                'total' => $tenantCount,
                'active_or_trialing' => $activeTenantCount,
                'suspended_or_inactive' => $suspendedTenantCount,
            ],
            'subscriptions' => [
                'by_status' => $subscriptionStatus,
                'by_plan' => $planUsage,
            ],
            'usage' => [
                'users' => User::withoutGlobalScope('tenant')->count(),
                'products' => Product::withoutGlobalScope('tenant')->count(),
                'customers' => Customer::withoutGlobalScope('tenant')->count(),
                'sales' => Sale::withoutGlobalScope('tenant')->count(),
                'sales_total' => (float) Sale::withoutGlobalScope('tenant')->sum('grand_total'),
            ],
            'backups' => [
                'queued' => TenantBackup::query()->where('status', TenantBackup::STATUS_QUEUED)->count(),
                'running' => TenantBackup::query()->where('status', TenantBackup::STATUS_RUNNING)->count(),
                'failed' => TenantBackup::query()->where('status', TenantBackup::STATUS_FAILED)->count(),
                'latest_completed_at' => TenantBackup::query()->where('status', TenantBackup::STATUS_COMPLETED)->max('completed_at'),
            ],
        ], 'Platform dashboard retrieved successfully.');
    }

    public function storePlan(Request $request): JsonResponse
    {
        $input = $this->validatePlan($request);
        $input['slug'] = $input['slug'] ?? Str::slug($input['name']);

        $plan = Plan::create($input);

        return $this->sendResponse($plan, 'Plan created successfully.');
    }

    public function updatePlan(Request $request, int $plan): JsonResponse
    {
        $plan = Plan::query()->findOrFail($plan);
        $input = $this->validatePlan($request, $plan->id);
        $input['slug'] = $input['slug'] ?? Str::slug($input['name']);

        $plan->update($input);

        return $this->sendResponse($plan->refresh(), 'Plan updated successfully.');
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $perPage = getPageSize($request);
        $query = Subscription::query()
            ->with('plan:id,name,slug,price,billing_cycle')
            ->leftJoin('stores', 'stores.tenant_id', '=', 'subscriptions.tenant_id')
            ->select('subscriptions.*', 'stores.name as tenant_name');

        foreach (['tenant_id', 'plan_id', 'status'] as $field) {
            if ($request->filled($field)) {
                $query->where("subscriptions.$field", $request->get($field));
            }
        }

        return $this->sendResponse($query->orderByDesc('subscriptions.id')->paginate($perPage), 'Subscriptions retrieved successfully.');
    }

    public function assignTenantPlan(Request $request, string $tenantId): JsonResponse
    {
        $input = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'status' => ['nullable', Rule::in([
                Subscription::STATUS_TRIALING,
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_PAST_DUE,
                Subscription::STATUS_CANCELED,
                Subscription::STATUS_SUSPENDED,
                Subscription::STATUS_EXPIRED,
            ])],
            'starts_at' => 'nullable|date',
            'trial_ends_at' => 'nullable|date',
            'renews_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'meta' => 'nullable|array',
        ]);

        $tenant = MultiTenant::query()->findOrFail($tenantId);

        $subscription = DB::transaction(function () use ($tenant, $input, $request) {
            $oldSubscription = Subscription::query()
                ->where('tenant_id', $tenant->id)
                ->latest('id')
                ->first();

            if ($oldSubscription && in_array($oldSubscription->status, [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING, Subscription::STATUS_PAST_DUE], true)) {
                $oldSubscription->update([
                    'status' => Subscription::STATUS_CANCELED,
                    'ends_at' => now(),
                ]);
            }

            $plan = Plan::query()->findOrFail($input['plan_id']);
            $status = $input['status'] ?? ($plan->trial_days > 0 ? Subscription::STATUS_TRIALING : Subscription::STATUS_ACTIVE);
            $startsAt = isset($input['starts_at']) ? Carbon::parse($input['starts_at']) : now();
            $trialEndsAt = $input['trial_ends_at'] ?? ($plan->trial_days > 0 ? $startsAt->copy()->addDays($plan->trial_days) : null);

            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => $status,
                'starts_at' => $startsAt,
                'trial_ends_at' => $trialEndsAt,
                'renews_at' => $input['renews_at'] ?? null,
                'ends_at' => $input['ends_at'] ?? null,
                'meta' => $input['meta'] ?? null,
            ]);

            $this->audit(
                AuditLog::UPDATED_TENANT_SUBSCRIPTION,
                $tenant->id,
                Subscription::class,
                $subscription->id,
                $oldSubscription?->only(['id', 'plan_id', 'status', 'starts_at', 'trial_ends_at', 'renews_at', 'ends_at']),
                $subscription->only(['id', 'plan_id', 'status', 'starts_at', 'trial_ends_at', 'renews_at', 'ends_at']),
                $request
            );

            return $subscription->load('plan:id,name,slug,price,billing_cycle');
        });

        return $this->sendResponse($subscription, 'Tenant subscription updated successfully.');
    }

    public function tenantUsage(string $tenantId): JsonResponse
    {
        MultiTenant::query()->findOrFail($tenantId);

        return $this->sendResponse(
            $this->tenantSubscriptionService->usage($tenantId),
            'Tenant subscription usage retrieved successfully.'
        );
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $perPage = getPageSize($request);
        $query = AuditLog::query()
            ->with('actor:id,first_name,last_name,email')
            ->leftJoin('stores', 'stores.tenant_id', '=', 'audit_logs.tenant_id')
            ->select('audit_logs.*', 'stores.name as tenant_name');

        foreach (['tenant_id', 'actor_id', 'event', 'auditable_type', 'auditable_id'] as $field) {
            if ($request->filled($field)) {
                $query->where("audit_logs.$field", $request->get($field));
            }
        }

        if ($request->filled('start_date')) {
            $query->whereDate('audit_logs.created_at', '>=', $request->get('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('audit_logs.created_at', '<=', $request->get('end_date'));
        }

        return $this->sendResponse($query->orderByDesc('audit_logs.id')->paginate($perPage), 'Audit logs retrieved successfully.');
    }

    public function backups(Request $request): JsonResponse
    {
        $perPage = getPageSize($request);
        $query = TenantBackup::query()
            ->with('requester:id,first_name,last_name,email')
            ->leftJoin('stores', 'stores.tenant_id', '=', 'tenant_backups.tenant_id')
            ->select('tenant_backups.*', 'stores.name as tenant_name');

        foreach (['tenant_id', 'status', 'backup_type'] as $field) {
            if ($request->filled($field)) {
                $query->where("tenant_backups.$field", $request->get($field));
            }
        }

        return $this->sendResponse($query->orderByDesc('tenant_backups.id')->paginate($perPage), 'Tenant backups retrieved successfully.');
    }

    public function requestBackup(Request $request, string $tenantId): JsonResponse
    {
        $input = $request->validate([
            'backup_type' => ['nullable', Rule::in(['full', 'database', 'files'])],
        ]);

        $tenant = MultiTenant::query()->findOrFail($tenantId);

        $backup = TenantBackup::create([
            'tenant_id' => $tenant->id,
            'requested_by' => Auth::id(),
            'status' => TenantBackup::STATUS_QUEUED,
            'backup_type' => $input['backup_type'] ?? 'full',
        ]);

        $this->audit(
            AuditLog::REQUESTED_TENANT_BACKUP,
            $tenant->id,
            TenantBackup::class,
            $backup->id,
            null,
            $backup->only(['id', 'status', 'backup_type']),
            $request
        );

        return $this->sendResponse($backup, 'Tenant backup queued successfully.');
    }

    public function updateBackupStatus(Request $request, int $backup): JsonResponse
    {
        $backup = TenantBackup::query()->findOrFail($backup);
        $oldValues = $backup->only(['status', 'disk', 'path', 'size_bytes', 'error_message', 'started_at', 'completed_at']);

        $input = $request->validate([
            'status' => ['required', Rule::in([
                TenantBackup::STATUS_QUEUED,
                TenantBackup::STATUS_RUNNING,
                TenantBackup::STATUS_COMPLETED,
                TenantBackup::STATUS_FAILED,
            ])],
            'disk' => 'nullable|string|max:255',
            'path' => 'nullable|string|max:1000',
            'size_bytes' => 'nullable|integer|min:0',
            'error_message' => 'nullable|string',
        ]);

        if ($input['status'] === TenantBackup::STATUS_RUNNING && !$backup->started_at) {
            $input['started_at'] = now();
        }

        if (in_array($input['status'], [TenantBackup::STATUS_COMPLETED, TenantBackup::STATUS_FAILED], true)) {
            $input['completed_at'] = now();
        }

        $backup->update($input);

        $this->audit(
            AuditLog::UPDATED_TENANT_BACKUP,
            $backup->tenant_id,
            TenantBackup::class,
            $backup->id,
            $oldValues,
            $backup->only(['status', 'disk', 'path', 'size_bytes', 'error_message', 'started_at', 'completed_at']),
            $request
        );

        return $this->sendResponse($backup->refresh(), 'Tenant backup updated successfully.');
    }

    public function downloadBackup(int $backup)
    {
        $backup = TenantBackup::query()->findOrFail($backup);

        if ($backup->status !== TenantBackup::STATUS_COMPLETED || !$backup->path) {
            return $this->sendError('Backup archive is not available.');
        }

        if (!Storage::disk('local')->exists($backup->path)) {
            return $this->sendError('Backup archive file was not found.', 404);
        }

        return Storage::disk('local')->download($backup->path);
    }

    private function validatePlan(Request $request, ?int $planId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('plans', 'slug')->ignore($planId)],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => ['required', Rule::in(['free', 'monthly', 'yearly'])],
            'trial_days' => 'nullable|integer|min:0',
            'max_stores' => 'nullable|integer|min:0',
            'max_shops' => 'nullable|integer|min:0',
            'max_registers' => 'nullable|integer|min:0',
            'max_users' => 'nullable|integer|min:0',
            'max_products' => 'nullable|integer|min:0',
            'features' => 'nullable|array',
            'status' => 'nullable|boolean',
        ]);
    }

    private function audit(
        string $event,
        ?string $tenantId,
        ?string $auditableType,
        ?int $auditableId,
        ?array $oldValues,
        ?array $newValues,
        Request $request
    ): void {
        AuditLog::create([
            'tenant_id' => $tenantId,
            'actor_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => now(),
        ]);
    }
}
