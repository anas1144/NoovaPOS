<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\CustomerDisplay;
use App\Models\CustomerOrder;
use App\Models\RestaurantTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Tenant management of customer displays (kiosks) and the orders they produce.
 */
class CustomerDisplayController extends AppBaseController
{
    // ── Displays ──────────────────────────────────────────────────────
    public function index(): JsonResponse
    {
        return $this->sendResponse(
            CustomerDisplay::query()->with(['store:id,name', 'kitchen:id,name'])->orderBy('name')->get(),
            'Customer displays retrieved successfully.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $data['token'] = Str::lower(Str::random(10));
        $display = CustomerDisplay::create($data);

        return $this->sendResponse($display, 'Customer display created successfully.');
    }

    public function update(Request $request, CustomerDisplay $customerDisplay): JsonResponse
    {
        $customerDisplay->update($this->validatePayload($request));

        return $this->sendResponse($customerDisplay->refresh(), 'Customer display updated successfully.');
    }

    public function destroy(CustomerDisplay $customerDisplay): JsonResponse
    {
        $customerDisplay->delete();

        return $this->sendSuccess('Customer display deleted successfully.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'store_id'   => 'required|integer',
            'shop_id'    => 'nullable|integer',
            'kitchen_id' => 'nullable|integer',
            'name'       => 'required|string|max:191',
            'status'     => 'nullable|boolean',
        ]);
    }

    // ── Orders (shop manager board) ───────────────────────────────────
    public function orders(Request $request): JsonResponse
    {
        $query = CustomerOrder::query()->orderByDesc('id');
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        return $this->sendResponse($query->limit(200)->get(), 'Customer orders retrieved successfully.');
    }

    public function acceptOrder(CustomerOrder $customerOrder): JsonResponse
    {
        $customerOrder->update(['status' => CustomerOrder::STATUS_ACCEPTED]);
        return $this->sendSuccess('Order accepted.');
    }

    public function assignOrder(Request $request, CustomerOrder $customerOrder): JsonResponse
    {
        $data = $request->validate(['waiter_id' => 'required|integer']);
        $customerOrder->update(['waiter_id' => $data['waiter_id'], 'status' => CustomerOrder::STATUS_ASSIGNED]);
        return $this->sendSuccess('Order assigned to waiter.');
    }

    public function serveOrder(CustomerOrder $customerOrder): JsonResponse
    {
        $customerOrder->update(['status' => CustomerOrder::STATUS_SERVED]);
        if ($customerOrder->table_id) {
            RestaurantTable::where('id', $customerOrder->table_id)->update(['state' => RestaurantTable::STATE_FREE]);
        }
        return $this->sendSuccess('Order served.');
    }
}
