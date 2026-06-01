<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\CustomerSubscription;
use App\Models\DeliverySchedule;
use App\Models\RecurringInvoice;
use App\Models\RecurringPlan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RecurringAPIController extends AppBaseController
{
    // ---- Plans ----
    public function plans(Request $request): JsonResponse
    {
        return $this->sendResponse(
            RecurringPlan::query()->orderBy('name')->get(),
            'Recurring plans retrieved successfully.'
        );
    }

    public function storePlan(Request $request): JsonResponse
    {
        $input = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'name' => 'required|string|max:255',
            'billing_cycle' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'price' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'is_bottle' => 'nullable|boolean',
            'status' => 'nullable|boolean',
        ]);
        return $this->sendResponse(RecurringPlan::create($input), 'Plan created successfully.');
    }

    public function updatePlan(Request $request, RecurringPlan $plan): JsonResponse
    {
        $input = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'name' => 'required|string|max:255',
            'billing_cycle' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'price' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'is_bottle' => 'nullable|boolean',
            'status' => 'nullable|boolean',
        ]);
        $plan->update($input);
        return $this->sendResponse($plan->refresh(), 'Plan updated successfully.');
    }

    public function destroyPlan(RecurringPlan $plan): JsonResponse
    {
        $plan->delete();
        return $this->sendSuccess('Plan deleted successfully.');
    }

    // ---- Customer subscriptions ----
    public function subscriptions(Request $request): JsonResponse
    {
        $query = CustomerSubscription::query()->with([
            'customer:id,name,phone',
            'plan:id,name,billing_cycle,price,is_bottle',
        ]);
        foreach (['status', 'route_name', 'customer_id'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->get($f));
            }
        }
        return $this->sendResponse(
            $query->orderByDesc('id')->paginate(getPageSize($request)),
            'Customer subscriptions retrieved successfully.'
        );
    }

    public function storeSubscription(Request $request): JsonResponse
    {
        $input = $this->validateSubscription($request);
        $input['next_invoice_date'] = $input['next_invoice_date'] ?? $this->computeNextInvoiceDate($input);
        $sub = CustomerSubscription::create($input);
        $this->generateSchedule($sub);
        return $this->sendResponse($sub->refresh(), 'Subscription created successfully.');
    }

    public function updateSubscription(Request $request, CustomerSubscription $subscription): JsonResponse
    {
        $input = $this->validateSubscription($request);
        $subscription->update($input);
        return $this->sendResponse($subscription->refresh(), 'Subscription updated successfully.');
    }

    public function pauseSubscription(CustomerSubscription $subscription): JsonResponse
    {
        $subscription->update(['status' => CustomerSubscription::STATUS_PAUSED]);
        return $this->sendResponse($subscription, 'Subscription paused.');
    }

    public function resumeSubscription(CustomerSubscription $subscription): JsonResponse
    {
        $subscription->update(['status' => CustomerSubscription::STATUS_ACTIVE]);
        return $this->sendResponse($subscription, 'Subscription resumed.');
    }

    public function destroySubscription(CustomerSubscription $subscription): JsonResponse
    {
        $subscription->update(['status' => CustomerSubscription::STATUS_ENDED]);
        return $this->sendSuccess('Subscription ended.');
    }

    // ---- Delivery schedules ----
    public function deliveries(Request $request): JsonResponse
    {
        $query = DeliverySchedule::query()->with([
            'customer:id,name,phone',
            'subscription:id,recurring_plan_id,default_quantity',
            'driver:id,first_name,last_name',
        ]);
        foreach (['status', 'route_name', 'customer_id', 'driver_id', 'customer_subscription_id'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->get($f));
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_date', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_date', '<=', $request->get('date_to'));
        }
        return $this->sendResponse(
            $query->orderBy('scheduled_date')->paginate(getPageSize($request)),
            'Deliveries retrieved successfully.'
        );
    }

    public function updateDelivery(Request $request, DeliverySchedule $delivery): JsonResponse
    {
        $input = $request->validate([
            'status' => ['required', Rule::in([
                DeliverySchedule::STATUS_SCHEDULED,
                DeliverySchedule::STATUS_DELIVERED,
                DeliverySchedule::STATUS_SKIPPED,
                DeliverySchedule::STATUS_PARTIAL,
                DeliverySchedule::STATUS_CANCELLED,
            ])],
            'extra_quantity' => 'nullable|numeric|min:0',
            'bottles_returned' => 'nullable|integer|min:0',
            'driver_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string',
        ]);

        $patch = $input;
        if ($input['status'] === DeliverySchedule::STATUS_DELIVERED) {
            $patch['delivered_at'] = now();
            // adjust bottles_with_customer on subscription
            $sub = $delivery->subscription;
            if ($sub && $sub->plan?->is_bottle) {
                $delivered = (int) $delivery->quantity + (int) ($input['extra_quantity'] ?? $delivery->extra_quantity);
                $returned = (int) ($input['bottles_returned'] ?? $delivery->bottles_returned);
                $sub->update([
                    'bottles_with_customer' => max(0, (int) $sub->bottles_with_customer + $delivered - $returned),
                ]);
            }
        }
        $delivery->update($patch);
        return $this->sendResponse($delivery->refresh(), 'Delivery updated successfully.');
    }

    // ---- Recurring invoices ----
    public function invoices(Request $request): JsonResponse
    {
        $query = RecurringInvoice::query()->with([
            'customer:id,name,phone',
            'subscription:id,recurring_plan_id',
        ]);
        foreach (['status', 'customer_id', 'customer_subscription_id'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->get($f));
            }
        }
        return $this->sendResponse(
            $query->orderByDesc('id')->paginate(getPageSize($request)),
            'Recurring invoices retrieved successfully.'
        );
    }

    public function generateInvoice(Request $request, CustomerSubscription $subscription): JsonResponse
    {
        $input = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'due_date' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($subscription, $input) {
            $deliveries = DeliverySchedule::where('customer_subscription_id', $subscription->id)
                ->where('status', DeliverySchedule::STATUS_DELIVERED)
                ->whereBetween('scheduled_date', [$input['period_start'], $input['period_end']])
                ->get();

            $plan = $subscription->plan;
            $unitPrice = (float) ($plan?->price ?? 0);
            $totalQty = 0;
            $lineItems = [];

            foreach ($deliveries as $d) {
                $qty = (float) $d->quantity + (float) $d->extra_quantity;
                $totalQty += $qty;
                $lineItems[] = [
                    'date' => $d->scheduled_date?->toDateString(),
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'amount' => round($qty * $unitPrice, 2),
                ];
            }

            $subtotal = round($totalQty * $unitPrice, 2);
            $invoice = RecurringInvoice::create([
                'customer_subscription_id' => $subscription->id,
                'customer_id' => $subscription->customer_id,
                'period_start' => $input['period_start'],
                'period_end' => $input['period_end'],
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $subtotal,
                'paid_amount' => 0,
                'due_date' => $input['due_date'] ?? Carbon::parse($input['period_end'])->addDays(7)->toDateString(),
                'invoice_no' => 'RINV-' . strtoupper(Str::random(7)),
                'status' => RecurringInvoice::STATUS_ISSUED,
                'line_items' => $lineItems,
            ]);

            return $this->sendResponse($invoice, 'Recurring invoice generated.');
        });
    }

    public function payInvoice(Request $request, RecurringInvoice $invoice): JsonResponse
    {
        $input = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);
        $paid = (float) $invoice->paid_amount + (float) $input['amount'];
        $status = $paid >= (float) $invoice->total_amount
            ? RecurringInvoice::STATUS_PAID
            : RecurringInvoice::STATUS_ISSUED;
        $invoice->update(['paid_amount' => $paid, 'status' => $status]);
        return $this->sendResponse($invoice, 'Payment recorded successfully.');
    }

    // ---- helpers ----
    private function validateSubscription(Request $request): array
    {
        return $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'recurring_plan_id' => 'required|exists:recurring_plans,id',
            'store_id' => 'nullable|exists:stores,id',
            'shop_id' => 'nullable|exists:shops,id',
            'default_quantity' => 'nullable|numeric|min:0',
            'schedule_days' => 'nullable|array',
            'schedule_days.*' => 'integer|min:1|max:7',
            'route_name' => 'nullable|string|max:255',
            'delivery_address' => 'nullable|string|max:500',
            'start_date' => 'nullable|date',
            'next_invoice_date' => 'nullable|date',
            'status' => ['nullable', Rule::in([
                CustomerSubscription::STATUS_ACTIVE,
                CustomerSubscription::STATUS_PAUSED,
                CustomerSubscription::STATUS_ENDED,
            ])],
            'deposit_paid' => 'nullable|numeric|min:0',
            'bottles_with_customer' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);
    }

    private function computeNextInvoiceDate(array $input): ?string
    {
        $start = isset($input['start_date']) ? Carbon::parse($input['start_date']) : now();
        return $start->copy()->addMonth()->toDateString();
    }

    /**
     * Generate the next 30 days of delivery rows from schedule_days.
     */
    private function generateSchedule(CustomerSubscription $sub): void
    {
        if (!$sub->schedule_days || count($sub->schedule_days) === 0) {
            return;
        }

        $start = $sub->start_date ?: now()->toDateString();
        $cursor = Carbon::parse($start);
        $end = $cursor->copy()->addDays(30);

        while ($cursor->lte($end)) {
            // Carbon: 1=Mon ... 7=Sun
            if (in_array($cursor->dayOfWeekIso, $sub->schedule_days, true)) {
                DeliverySchedule::create([
                    'customer_subscription_id' => $sub->id,
                    'customer_id' => $sub->customer_id,
                    'scheduled_date' => $cursor->toDateString(),
                    'quantity' => $sub->default_quantity,
                    'extra_quantity' => 0,
                    'route_name' => $sub->route_name,
                    'status' => DeliverySchedule::STATUS_SCHEDULED,
                ]);
            }
            $cursor->addDay();
        }
    }
}
