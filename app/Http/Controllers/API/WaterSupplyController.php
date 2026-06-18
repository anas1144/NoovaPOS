<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\BottleTransaction;
use App\Models\DeliveryRoute;
use App\Models\WaterDeposit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Water Supply module: delivery routes, reusable-bottle ledger (issue/return +
 * balances) and customer deposits.
 */
class WaterSupplyController extends AppBaseController
{
    // ── Routes ────────────────────────────────────────────────────────────────

    public function routes(Request $request): JsonResponse
    {
        return $this->sendResponse(
            DeliveryRoute::query()->orderBy('name')->get(),
            'Routes retrieved.'
        );
    }

    public function storeRoute(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:120',
            'area'           => 'nullable|string|max:191',
            'driver_user_id' => 'nullable|integer',
            'status'         => 'nullable|boolean',
        ]);
        $route = DeliveryRoute::create($data);
        return $this->sendResponse($route, 'Route created.');
    }

    public function updateRoute(Request $request, DeliveryRoute $deliveryRoute): JsonResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:120',
            'area'           => 'nullable|string|max:191',
            'driver_user_id' => 'nullable|integer',
            'status'         => 'nullable|boolean',
        ]);
        $deliveryRoute->update($data);
        return $this->sendResponse($deliveryRoute, 'Route updated.');
    }

    public function destroyRoute(DeliveryRoute $deliveryRoute): JsonResponse
    {
        $deliveryRoute->delete();
        return $this->sendSuccess('Route removed.');
    }

    // ── Bottle ledger ──────────────────────────────────────────────────────────

    public function bottles(Request $request): JsonResponse
    {
        $rows = BottleTransaction::query()
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->get('customer_id')))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate((int) $request->get('page_size', 25));

        return $this->sendResponse($rows, 'Bottle ledger retrieved.');
    }

    public function recordBottles(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id'       => 'required|integer',
            'delivery_route_id' => 'nullable|integer',
            'type'              => 'required|in:issue,return',
            'quantity'          => 'required|numeric|min:0.01',
            'date'              => 'required|date',
            'note'              => 'nullable|string|max:191',
        ]);
        $data['created_by'] = $request->user()?->id;

        $tx = BottleTransaction::create($data);
        return $this->sendResponse([
            'transaction' => $tx,
            'balance'     => $this->bottleBalance($data['customer_id']),
        ], 'Bottle movement recorded.');
    }

    /** Per-customer outstanding bottle balance = issued - returned. */
    public function bottleBalances(Request $request): JsonResponse
    {
        $rows = BottleTransaction::query()
            ->select('customer_id')
            ->selectRaw("SUM(CASE WHEN type='issue' THEN quantity ELSE -quantity END) as balance")
            ->groupBy('customer_id')
            ->having('balance', '<>', 0)
            ->get();

        return $this->sendResponse($rows, 'Bottle balances retrieved.');
    }

    private function bottleBalance($customerId): float
    {
        return (float) BottleTransaction::query()
            ->where('customer_id', $customerId)
            ->selectRaw("SUM(CASE WHEN type='issue' THEN quantity ELSE -quantity END) as balance")
            ->value('balance');
    }

    // ── Deposits ────────────────────────────────────────────────────────────────

    public function deposits(Request $request): JsonResponse
    {
        $rows = WaterDeposit::query()
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->get('customer_id')))
            ->orderByDesc('id')
            ->paginate((int) $request->get('page_size', 25));

        return $this->sendResponse($rows, 'Deposits retrieved.');
    }

    public function storeDeposit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => 'required|integer',
            'amount'      => 'required|numeric|min:0',
            'bottles'     => 'nullable|numeric|min:0',
            'date'        => 'required|date',
            'note'        => 'nullable|string|max:191',
        ]);
        $data['status'] = 'held';
        $deposit = WaterDeposit::create($data);
        return $this->sendResponse($deposit, 'Deposit recorded.');
    }

    public function refundDeposit(Request $request, WaterDeposit $waterDeposit): JsonResponse
    {
        if ($waterDeposit->status === 'refunded') {
            return $this->sendError('Deposit already refunded.', 422);
        }
        $waterDeposit->update(['status' => 'refunded', 'refunded_at' => now()]);
        return $this->sendResponse($waterDeposit, 'Deposit refunded.');
    }
}
