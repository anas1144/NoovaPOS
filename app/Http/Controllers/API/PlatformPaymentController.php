<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\AuditLog;
use App\Models\MultiTenant;
use App\Models\Plan;
use App\Models\Store;
use App\Models\SubscriptionPayment;
use App\Services\TenantSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Super-admin view of all tenant subscription payments, plus confirm/reject.
 * Confirming a payment activates the tenant's subscription for the paid period.
 */
class PlatformPaymentController extends AppBaseController
{
    public function __construct(private readonly TenantSubscriptionService $subscriptionService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = SubscriptionPayment::query()->with('plan:id,name')->latest('id');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($tenantId = $request->get('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        $payments = $query->get();

        // Attach a friendly tenant/business name (default store name).
        $storeNames = Store::withoutGlobalScope('tenant')
            ->whereIn('tenant_id', $payments->pluck('tenant_id'))
            ->where('is_default', true)
            ->pluck('name', 'tenant_id');

        $data = $payments->map(fn ($p) => [
            'id'            => $p->id,
            'tenant_id'     => $p->tenant_id,
            'tenant_name'   => $storeNames[$p->tenant_id] ?? $p->tenant_id,
            'type'          => $p->type ?? 'subscription',
            'addon_summary' => $p->type === 'addon'
                ? trim(collect([
                    $p->addon_shops ? "{$p->addon_shops} shops" : null,
                    $p->addon_users ? "{$p->addon_users} users" : null,
                    $p->addon_products ? "{$p->addon_products} products" : null,
                ])->filter()->implode(', '))
                : ($p->type === 'fbr' ? "FBR · {$p->periods} {$p->billing_cycle}" : null),
            'plan_name'     => $p->plan?->name,
            'billing_cycle' => $p->billing_cycle,
            'periods'       => $p->periods,
            'amount'        => $p->amount,
            'currency'      => $p->currency,
            'status'        => $p->status,
            'method'        => $p->method,
            'reference'     => $p->reference,
            'proof_url'     => $p->proof_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($p->proof_path) : null,
            'period_start'  => $p->period_start,
            'period_end'    => $p->period_end,
            'paid_at'       => $p->paid_at,
            'created_at'    => $p->created_at,
        ]);

        return $this->sendResponse($data, 'Subscription payments retrieved successfully.');
    }

    public function confirm(Request $request, SubscriptionPayment $payment): JsonResponse
    {
        if ($payment->status === SubscriptionPayment::STATUS_PAID) {
            return $this->sendError('This payment is already confirmed.', 422);
        }

        // Add-on purchase: raise the tenant's allowance, no subscription change.
        if ($payment->type === 'addon') {
            DB::transaction(function () use ($request, $payment) {
                $this->subscriptionService->applyAddons(
                    $payment->tenant_id,
                    (int) $payment->addon_shops,
                    (int) $payment->addon_users,
                    (int) $payment->addon_products
                );

                $payment->update([
                    'status'       => SubscriptionPayment::STATUS_PAID,
                    'paid_at'      => now(),
                    'confirmed_by' => $request->user()->id,
                ]);
            });

            return $this->sendSuccess('Add-on payment confirmed; tenant limits increased.');
        }

        // FBR activation purchase.
        if ($payment->type === 'fbr') {
            DB::transaction(function () use ($request, $payment) {
                $this->subscriptionService->applyFbr(
                    $payment->tenant_id,
                    $payment->billing_cycle,
                    (int) $payment->periods
                );

                $payment->update([
                    'status'       => SubscriptionPayment::STATUS_PAID,
                    'paid_at'      => now(),
                    'confirmed_by' => $request->user()->id,
                ]);
            });

            return $this->sendSuccess('FBR payment confirmed; FBR activated for the tenant.');
        }

        $plan = Plan::find($payment->plan_id);
        if (! $plan) {
            return $this->sendError('Plan for this payment no longer exists.', 422);
        }

        DB::transaction(function () use ($request, $payment, $plan) {
            $subscription = $this->subscriptionService->activateForPeriod(
                $payment->tenant_id,
                $plan,
                $payment->billing_cycle,
                $payment->periods
            );

            $payment->update([
                'status'       => SubscriptionPayment::STATUS_PAID,
                'paid_at'      => now(),
                'period_start' => $subscription->starts_at,
                'period_end'   => $subscription->ends_at,
                'confirmed_by' => $request->user()->id,
            ]);

            AuditLog::create([
                'tenant_id'      => $payment->tenant_id,
                'actor_id'       => $request->user()->id,
                'event'          => AuditLog::UPDATED_TENANT_SUBSCRIPTION,
                'auditable_type' => SubscriptionPayment::class,
                'auditable_id'   => $payment->id,
                'ip_address'     => $request->ip(),
                'user_agent'     => substr((string) $request->userAgent(), 0, 1000),
                'old_values'     => ['status' => 'pending'],
                'new_values'     => ['status' => 'paid', 'period_end' => (string) $subscription->ends_at],
                'created_at'     => now(),
            ]);
        });

        return $this->sendSuccess('Payment confirmed and subscription activated.');
    }

    public function reject(Request $request, SubscriptionPayment $payment): JsonResponse
    {
        if ($payment->status === SubscriptionPayment::STATUS_PAID) {
            return $this->sendError('A confirmed payment cannot be rejected.', 422);
        }

        $payment->update([
            'status'       => SubscriptionPayment::STATUS_REJECTED,
            'confirmed_by' => $request->user()->id,
            'note'         => trim(($payment->note ? $payment->note . ' | ' : '') . (string) $request->get('note', 'Rejected')),
        ]);

        return $this->sendSuccess('Payment rejected.');
    }
}
