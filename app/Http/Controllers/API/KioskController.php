<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\CustomerDisplay;
use App\Models\CustomerOrder;
use App\Models\Deal;
use App\Models\KitchenOrderItem;
use App\Models\KitchenOrderTicket;
use App\Models\Product;
use App\Models\RestaurantHall;
use App\Models\RestaurantTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Public (no-login) customer self-service kiosk, reached by display token.
 */
class KioskController extends AppBaseController
{
    private function display(string $token): ?CustomerDisplay
    {
        return CustomerDisplay::withoutGlobalScope('tenant')
            ->where('token', $token)->where('status', true)->first();
    }

    public function config(string $token): JsonResponse
    {
        $display = $this->display($token);
        if (! $display) {
            return $this->sendError('Display not found or disabled.', 404);
        }

        $tid = $display->tenant_id;

        $products = Product::withoutGlobalScope('tenant')->where('tenant_id', $tid)
            ->get(['id', 'name', 'product_price'])
            ->map(fn ($p) => ['type' => 'product', 'id' => $p->id, 'name' => $p->name, 'price' => (float) $p->product_price]);

        $deals = Deal::withoutGlobalScope('tenant')->where('tenant_id', $tid)->where('status', true)
            ->get(['id', 'name', 'price'])
            ->map(fn ($d) => ['type' => 'deal', 'id' => $d->id, 'name' => $d->name, 'price' => (float) $d->price]);

        $halls = RestaurantHall::withoutGlobalScope('tenant')->where('tenant_id', $tid)
            ->where('store_id', $display->store_id)->get(['id', 'name']);
        $tables = RestaurantTable::withoutGlobalScope('tenant')->where('tenant_id', $tid)
            ->where('store_id', $display->store_id)->get(['id', 'hall_id', 'name', 'seats', 'state']);

        return $this->sendResponse([
            'display'  => ['name' => $display->name, 'token' => $display->token],
            'products' => $products->values(),
            'deals'    => $deals->values(),
            'halls'    => $halls,
            'tables'   => $tables,
        ], 'Kiosk config retrieved.');
    }

    public function order(Request $request, string $token): JsonResponse
    {
        $display = $this->display($token);
        if (! $display) {
            return $this->sendError('Display not found or disabled.', 404);
        }

        $data = $request->validate([
            'customer_name'      => 'nullable|string|max:120',
            'table_id'           => 'nullable|integer',
            'seats'              => 'nullable|array',
            'items'              => 'required|array|min:1',
            'items.*.type'       => 'required|in:product,deal',
            'items.*.id'         => 'required|integer',
            'items.*.name'       => 'required|string|max:191',
            'items.*.price'      => 'required|numeric|min:0',
            'items.*.quantity'   => 'required|numeric|min:0.01',
        ]);

        $total = collect($data['items'])->sum(fn ($i) => $i['price'] * $i['quantity']);
        $tokenNo = 'T-' . strtoupper(Str::random(5));

        return DB::transaction(function () use ($display, $data, $total, $tokenNo) {
            // KOT routed to the display's kitchen.
            $kot = KitchenOrderTicket::create([
                'tenant_id'  => $display->tenant_id,
                'store_id'   => $display->store_id,
                'shop_id'    => $display->shop_id,
                'kitchen_id' => $display->kitchen_id,
                'table_id'   => $data['table_id'] ?? null,
                'order_type' => 'dine_in',
                'ticket_no'  => 'KOT-' . strtoupper(Str::random(6)),
                'status'     => KitchenOrderTicket::STATUS_SENT,
                'sent_to_kitchen_at' => now(),
            ]);
            foreach ($data['items'] as $i) {
                KitchenOrderItem::create([
                    'ticket_id'    => $kot->id,
                    'product_id'   => $i['type'] === 'product' ? $i['id'] : null,
                    'product_name' => $i['name'],
                    'quantity'     => $i['quantity'],
                    'status'       => 'pending',
                ]);
            }

            $order = CustomerOrder::create([
                'tenant_id'     => $display->tenant_id,
                'store_id'      => $display->store_id,
                'shop_id'       => $display->shop_id,
                'display_id'    => $display->id,
                'kitchen_id'    => $display->kitchen_id,
                'table_id'      => $data['table_id'] ?? null,
                'kot_id'        => $kot->id,
                'token_no'      => $tokenNo,
                'customer_name' => $data['customer_name'] ?? null,
                'seats'         => $data['seats'] ?? null,
                'items'         => $data['items'],
                'total'         => $total,
                'status'        => CustomerOrder::STATUS_NEW,
            ]);

            if ($order->table_id) {
                RestaurantTable::where('id', $order->table_id)->update(['state' => RestaurantTable::STATE_OCCUPIED]);
            }

            return $this->sendResponse([
                'token_no' => $tokenNo,
                'order_id' => $order->id,
                'total'    => $total,
            ], 'Order placed. Show your token at the counter.');
        });
    }
}
