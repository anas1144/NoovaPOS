<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Services\TenantSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tenant-facing billing/subscription endpoints.
 *
 * IMPORTANT: these routes are intentionally OUTSIDE the `tenant.active`
 * middleware so a tenant whose trial/subscription has expired can still reach
 * their Billing page to renew (hard lock everywhere except billing).
 */
class TenantBillingController extends AppBaseController
{
    public function __construct(private readonly TenantSubscriptionService $subscriptionService)
    {
    }

    public function overview(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;

        if (! $tenantId) {
            return $this->sendResponse([
                'is_platform_user' => true,
            ], 'No tenant billing for this user.');
        }

        $subscription = $this->subscriptionService->latestSubscription($tenantId);
        $usage = $this->subscriptionService->usage($tenantId);

        $now = now();
        $trialEndsAt = $subscription?->trial_ends_at;
        $endsAt = $subscription?->ends_at;

        $isTrialing = $subscription?->status === 'trialing';
        $trialDaysLeft = ($isTrialing && $trialEndsAt) ? max(0, $now->diffInDays($trialEndsAt, false)) : null;

        $isExpired = false;
        if ($subscription) {
            $isExpired = in_array($subscription->status, ['canceled', 'suspended', 'expired'], true)
                || ($endsAt && $endsAt->isPast())
                || ($isTrialing && $trialEndsAt && $trialEndsAt->isPast());
        }

        // Country-based plans: a plan is shown if it has no country restriction,
        // or if it explicitly allows the tenant's country.
        $country = $request->user()?->country;
        $plans = Plan::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'price', 'price_yearly', 'currency', 'billing_cycle', 'trial_days', 'allowed_countries', 'max_stores', 'max_shops', 'max_registers', 'max_users', 'max_products'])
            ->filter(function ($plan) use ($country) {
                $allowed = $plan->allowed_countries;
                if (empty($allowed)) {
                    return true; // no restriction
                }
                return $country && in_array($country, $allowed, true);
            })
            ->values();

        $pending = SubscriptionPayment::query()
            ->where('tenant_id', $tenantId)
            ->where('status', SubscriptionPayment::STATUS_PENDING)
            ->latest('id')
            ->first();

        // Recently rejected payments — surfaced so the tenant knows to re-submit.
        $rejected = SubscriptionPayment::query()
            ->where('tenant_id', $tenantId)
            ->where('status', SubscriptionPayment::STATUS_REJECTED)
            ->latest('id')
            ->take(5)
            ->get(['id', 'type', 'amount', 'currency', 'note', 'updated_at']);

        // Payout bank accounts for the tenant's country (where to send payment).
        $bankAccounts = \App\Models\BankAccount::query()
            ->where('status', true)
            ->when($country, fn ($q) => $q->where('country', $country))
            ->orderBy('bank_name')
            ->get();

        return $this->sendResponse([
            'subscription' => $subscription ? [
                'status'        => $subscription->status,
                'plan'          => $subscription->plan?->only(['id', 'name', 'slug']),
                'trial_ends_at' => $trialEndsAt,
                'ends_at'       => $endsAt,
                'trial_days_left' => $trialDaysLeft,
                'is_expired'    => $isExpired,
            ] : null,
            'limits'        => $usage['limits'],
            'usage'         => $usage['usage'],
            'plans'         => $plans,
            'pending_payment' => $pending,
            'rejected_payments' => $rejected,
            'bank_accounts' => $bankAccounts,
            'discount'      => [
                'percent'    => \App\Models\PlatformSetting::getFloatForCountry('long_term_discount_percent', $country, 0),
                'min_months' => (int) \App\Models\PlatformSetting::getFloatForCountry('long_term_discount_min_months', $country, 12),
            ],
            'addon_rates'   => [
                'shop'    => \App\Models\PlatformSetting::getFloatForCountry('addon_shop_rate', $country, 0),
                'user'    => \App\Models\PlatformSetting::getFloatForCountry('addon_user_rate', $country, 0),
                'product' => \App\Models\PlatformSetting::getFloatForCountry('addon_product_rate', $country, 0),
            ],
            'extras'        => $usage['extras'] ?? ['shops' => 0, 'users' => 0, 'products' => 0],
            'fbr'           => [
                // FBR is available only to Pakistan tenants.
                'eligible'      => strtoupper((string) $country) === 'PK',
                'monthly_price' => $this->subscriptionService->fbrPrice('monthly', $country),
                'yearly_price'  => $this->subscriptionService->fbrPrice('yearly', $country),
                'status'        => $this->subscriptionService->fbrStatus($tenantId),
            ],
            // Which payment methods the super admin has enabled, plus the online
            // checkout channels available for this tenant's country.
            'methods'       => [
                'request'  => (\App\Models\PlatformSetting::get('billing.method.request') ?? '1') === '1',
                'checkout' => (\App\Models\PlatformSetting::get('billing.method.checkout') ?? '0') === '1',
            ],
            'checkout_channels' => \App\Http\Controllers\API\PlatformSettingController::channelsFor($country),
        ], 'Billing overview retrieved successfully.');
    }

    public function requestPayment(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        if (! $tenantId) {
            return $this->sendError('Only tenant users can request a subscription.', 422);
        }

        $data = $request->validate([
            'plan_id'       => 'required|integer',
            'billing_cycle' => 'required|in:monthly,yearly',
            'periods'       => 'required|integer|min:1|max:60',
            'method'        => 'nullable|in:manual,bank_transfer,gateway',
            'reference'     => 'nullable|string|max:191',
            'note'          => 'nullable|string|max:1000',
            // Payment proof (screenshot / receipt) is required.
            'proof'         => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            // Optional add-ons bought together with the plan (created as a
            // SEPARATE add-on payment so the super admin manages it on its own).
            'addon_shops'    => 'nullable|integer|min:0|max:1000',
            'addon_users'    => 'nullable|integer|min:0|max:1000',
            'addon_products' => 'nullable|integer|min:0|max:100000',
        ]);

        // Plan is central — explicit find avoids cross-DB exists rule issues.
        $plan = Plan::find($data['plan_id']);
        if (! $plan) {
            return $this->sendError('Selected plan was not found.', 422);
        }

        $country = $request->user()?->country;
        $amount = $this->subscriptionService->amountFor($plan, $data['billing_cycle'], $data['periods'], $country);

        $proofPath = $request->file('proof')->store('subscription_proofs', 'public');

        $payment = SubscriptionPayment::create([
            'tenant_id'     => $tenantId,
            'plan_id'       => $plan->id,
            'billing_cycle' => $data['billing_cycle'],
            'periods'       => $data['periods'],
            'amount'        => $amount,
            'currency'      => $plan->currency ?? 'USD',
            'status'        => SubscriptionPayment::STATUS_PENDING,
            'method'        => $data['method'] ?? 'manual',
            'reference'     => $data['reference'] ?? null,
            'proof_path'    => $proofPath,
            'note'          => $data['note'] ?? null,
            'requested_by'  => $request->user()->id,
        ]);

        // If add-ons were bought alongside the plan, record them as a SEPARATE
        // pending add-on payment (super admin confirms each independently). The
        // same proof image is reused.
        $aShops = (int) ($data['addon_shops'] ?? 0);
        $aUsers = (int) ($data['addon_users'] ?? 0);
        $aProducts = (int) ($data['addon_products'] ?? 0);
        if ($aShops + $aUsers + $aProducts > 0) {
            SubscriptionPayment::create([
                'tenant_id'      => $tenantId,
                'type'           => 'addon',
                'billing_cycle'  => 'monthly',
                'periods'        => 1,
                'addon_shops'    => $aShops,
                'addon_users'    => $aUsers,
                'addon_products' => $aProducts,
                'amount'         => $this->subscriptionService->addonAmount($aShops, $aUsers, $aProducts, $country),
                'currency'       => $plan->currency ?? 'USD',
                'status'         => SubscriptionPayment::STATUS_PENDING,
                'method'         => $data['method'] ?? 'bank_transfer',
                'reference'      => $data['reference'] ?? null,
                'proof_path'     => $proofPath,
                'requested_by'   => $request->user()->id,
            ]);
        }

        return $this->sendResponse($payment, 'Subscription request submitted. Awaiting confirmation.');
    }

    /**
     * Online checkout for a plan subscription (and optional add-ons).
     *
     * Unlike requestPayment (manual proof upload), checkout uses a configured
     * channel — a global hosted "link", or a Pakistan local wallet/bank
     * (JazzCash, Easypaisa, HBL, Meezan, UBL…). No paid gateway SDK is required:
     * we record a pending payment with the chosen channel + tenant reference and
     * a token for the hosted pay page; the super admin confirms it like any
     * other payment, which activates the subscription.
     */
    public function checkout(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        if (! $tenantId) {
            return $this->sendError('Only tenant users can check out.', 422);
        }

        if ((\App\Models\PlatformSetting::get('billing.method.checkout') ?? '0') !== '1') {
            return $this->sendError('Online checkout is not enabled.', 422);
        }

        $data = $request->validate([
            'plan_id'        => 'required|integer',
            'billing_cycle'  => 'required|in:monthly,yearly',
            'periods'        => 'required|integer|min:1|max:60',
            'channel'        => 'required|string|max:40',
            'reference'      => 'nullable|string|max:191',
            'note'           => 'nullable|string|max:1000',
            // Proof is optional for checkout (wallet/bank screenshots welcome).
            'proof'          => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'addon_shops'    => 'nullable|integer|min:0|max:1000',
            'addon_users'    => 'nullable|integer|min:0|max:1000',
            'addon_products' => 'nullable|integer|min:0|max:100000',
        ]);

        $plan = Plan::find($data['plan_id']);
        if (! $plan) {
            return $this->sendError('Selected plan was not found.', 422);
        }

        $country = $request->user()?->country;

        // The channel must be one configured for this country.
        $channels = \App\Http\Controllers\API\PlatformSettingController::channelsFor($country);
        $channel = collect($channels)->firstWhere('key', $data['channel']);
        if (! $channel) {
            return $this->sendError('Selected payment channel is not available.', 422);
        }

        $amount = $this->subscriptionService->amountFor($plan, $data['billing_cycle'], $data['periods'], $country);
        $token = \Illuminate\Support\Str::random(40);
        $proofPath = $request->hasFile('proof')
            ? $request->file('proof')->store('subscription_proofs', 'public')
            : null;

        $payment = SubscriptionPayment::create([
            'tenant_id'          => $tenantId,
            'plan_id'            => $plan->id,
            'billing_cycle'      => $data['billing_cycle'],
            'periods'            => $data['periods'],
            'amount'             => $amount,
            'currency'           => $plan->currency ?? 'USD',
            'status'             => SubscriptionPayment::STATUS_PENDING,
            'method'             => 'gateway',
            'pay_method'         => 'checkout',
            'checkout_channel'   => $data['channel'],
            'checkout_reference' => $data['reference'] ?? null,
            'checkout_token'     => $token,
            'proof_path'         => $proofPath,
            'note'               => $data['note'] ?? null,
            'requested_by'       => $request->user()->id,
        ]);

        // Optional add-ons → a separate pending checkout payment.
        $aShops = (int) ($data['addon_shops'] ?? 0);
        $aUsers = (int) ($data['addon_users'] ?? 0);
        $aProducts = (int) ($data['addon_products'] ?? 0);
        if ($aShops + $aUsers + $aProducts > 0) {
            SubscriptionPayment::create([
                'tenant_id'          => $tenantId,
                'type'               => 'addon',
                'billing_cycle'      => 'monthly',
                'periods'            => 1,
                'addon_shops'        => $aShops,
                'addon_users'        => $aUsers,
                'addon_products'     => $aProducts,
                'amount'             => $this->subscriptionService->addonAmount($aShops, $aUsers, $aProducts, $country),
                'currency'           => $plan->currency ?? 'USD',
                'status'             => SubscriptionPayment::STATUS_PENDING,
                'method'             => 'gateway',
                'pay_method'         => 'checkout',
                'checkout_channel'   => $data['channel'],
                'checkout_reference' => $data['reference'] ?? null,
                'checkout_token'     => \Illuminate\Support\Str::random(40),
                'requested_by'       => $request->user()->id,
            ]);
        }

        return $this->sendResponse([
            'payment'      => $payment,
            'channel'      => $channel,
            'checkout_url' => url('/billing/pay/' . $token),
        ], 'Checkout created. Complete the payment to activate your subscription.');
    }

    /**
     * Tenant requests an add-on purchase (extra shops/users/products). Like a
     * subscription request: requires proof and awaits super-admin confirmation,
     * which then raises the tenant's limits.
     */
    public function requestAddon(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        if (! $tenantId) {
            return $this->sendError('Only tenant users can buy add-ons.', 422);
        }

        $data = $request->validate([
            'shops'     => 'nullable|integer|min:0|max:1000',
            'users'     => 'nullable|integer|min:0|max:1000',
            'products'  => 'nullable|integer|min:0|max:100000',
            'method'    => 'nullable|in:manual,bank_transfer,gateway',
            'reference' => 'nullable|string|max:191',
            'proof'     => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $shops    = (int) ($data['shops'] ?? 0);
        $users    = (int) ($data['users'] ?? 0);
        $products = (int) ($data['products'] ?? 0);

        if ($shops + $users + $products <= 0) {
            return $this->sendError('Select at least one add-on quantity.', 422);
        }

        $amount = $this->subscriptionService->addonAmount($shops, $users, $products, $request->user()?->country);
        $proofPath = $request->file('proof')->store('subscription_proofs', 'public');

        $payment = SubscriptionPayment::create([
            'tenant_id'      => $tenantId,
            'type'           => 'addon',
            'billing_cycle'  => 'monthly',
            'periods'        => 1,
            'addon_shops'    => $shops,
            'addon_users'    => $users,
            'addon_products' => $products,
            'amount'         => $amount,
            'currency'       => 'USD',
            'status'         => SubscriptionPayment::STATUS_PENDING,
            'method'         => $data['method'] ?? 'bank_transfer',
            'reference'      => $data['reference'] ?? null,
            'proof_path'     => $proofPath,
            'requested_by'   => $request->user()->id,
        ]);

        return $this->sendResponse($payment, 'Add-on request submitted. Awaiting confirmation.');
    }

    /**
     * Pakistan-only: tenant requests to enable/extend FBR for N months/years.
     */
    public function requestFbr(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        if (! $tenantId) {
            return $this->sendError('Only tenant users can enable FBR.', 422);
        }
        if (strtoupper((string) $user->country) !== 'PK') {
            return $this->sendError('FBR is available only for Pakistan.', 422);
        }

        $data = $request->validate([
            'billing_cycle' => 'required|in:monthly,yearly',
            'periods'       => 'required|integer|min:1|max:60',
            'method'        => 'nullable|in:manual,bank_transfer,gateway',
            'reference'     => 'nullable|string|max:191',
            'proof'         => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $unit = $this->subscriptionService->fbrPrice($data['billing_cycle'], $user->country);
        if ($unit <= 0) {
            return $this->sendError('FBR pricing is not configured yet. Please contact support.', 422);
        }
        $amount = round($unit * $data['periods'], 2);
        $proofPath = $request->file('proof')->store('subscription_proofs', 'public');

        $payment = SubscriptionPayment::create([
            'tenant_id'     => $tenantId,
            'type'          => 'fbr',
            'billing_cycle' => $data['billing_cycle'],
            'periods'       => $data['periods'],
            'amount'        => $amount,
            'currency'      => 'PKR',
            'status'        => SubscriptionPayment::STATUS_PENDING,
            'method'        => $data['method'] ?? 'bank_transfer',
            'reference'     => $data['reference'] ?? null,
            'proof_path'    => $proofPath,
            'requested_by'  => $user->id,
        ]);

        return $this->sendResponse($payment, 'FBR request submitted. Awaiting confirmation.');
    }
}
