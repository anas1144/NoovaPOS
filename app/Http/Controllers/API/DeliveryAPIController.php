<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Delivery management: list delivery orders, assign a delivery boy, and track
 * delivery status.
 */
class DeliveryAPIController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Sale::query()
            ->where('order_type', 'delivery')
            ->with(['customer:id,name,phone'])
            ->orderByDesc('id');

        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->get('delivery_status'));
        }

        $sales = $query->get();

        $boyIds = $sales->pluck('delivery_boy_id')->filter()->unique();
        $boys = User::withoutGlobalScope('tenant')->whereIn('id', $boyIds)
            ->get(['id', 'first_name', 'last_name'])->keyBy('id');

        $data = $sales->map(fn ($s) => [
            'id'               => $s->id,
            'reference_code'   => $s->reference_code,
            'customer'         => $s->customer?->name,
            'phone'            => $s->customer?->phone,
            'grand_total'      => $s->grand_total,
            'delivery_address' => $s->delivery_address,
            'delivery_status'  => $s->delivery_status ?? 'pending',
            'delivery_boy_id'  => $s->delivery_boy_id,
            'delivery_boy'     => $s->delivery_boy_id && $boys->get($s->delivery_boy_id)
                ? trim($boys[$s->delivery_boy_id]->first_name . ' ' . $boys[$s->delivery_boy_id]->last_name)
                : null,
            'created_at'       => $s->created_at,
        ]);

        return $this->sendResponse($data, 'Delivery orders retrieved successfully.');
    }

    public function deliveryBoys(): JsonResponse
    {
        $boys = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', Role::DELIVERY_BOY))
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => trim($u->first_name . ' ' . $u->last_name)]);

        return $this->sendResponse($boys, 'Delivery boys retrieved successfully.');
    }

    public function assign(Request $request, Sale $sale): JsonResponse
    {
        $data = $request->validate(['delivery_boy_id' => 'required|integer']);
        $sale->update([
            'delivery_boy_id' => $data['delivery_boy_id'],
            'delivery_status' => 'assigned',
        ]);

        return $this->sendSuccess('Delivery boy assigned.');
    }

    public function updateStatus(Request $request, Sale $sale): JsonResponse
    {
        $data = $request->validate([
            'delivery_status' => 'required|in:pending,assigned,out_for_delivery,delivered,cancelled',
        ]);
        $sale->update(['delivery_status' => $data['delivery_status']]);

        return $this->sendSuccess('Delivery status updated.');
    }
}
