<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\KitchenOrderItem;
use App\Models\KitchenOrderTicket;
use App\Models\RestaurantHall;
use App\Models\RestaurantTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RestaurantAPIController extends AppBaseController
{
    // -------------------- Halls --------------------
    public function halls(Request $request): JsonResponse
    {
        $query = RestaurantHall::query();
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->get('store_id'));
        }
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->get('shop_id'));
        }
        return $this->sendResponse(
            $query->withCount('tables')->orderBy('name')->get(),
            'Halls retrieved successfully.'
        );
    }

    public function storeHall(Request $request): JsonResponse
    {
        $input = $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'shop_id' => 'nullable|exists:shops,id',
            'kitchen_id' => 'nullable|exists:kitchens,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'floor' => 'nullable|integer|min:0',
            'capacity' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);
        $hall = RestaurantHall::create($input);
        return $this->sendResponse($hall, 'Hall created successfully.');
    }

    public function updateHall(Request $request, RestaurantHall $hall): JsonResponse
    {
        $input = $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'shop_id' => 'nullable|exists:shops,id',
            'kitchen_id' => 'nullable|exists:kitchens,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'floor' => 'nullable|integer|min:0',
            'capacity' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);
        $hall->update($input);
        return $this->sendResponse($hall->refresh(), 'Hall updated successfully.');
    }

    public function destroyHall(RestaurantHall $hall): JsonResponse
    {
        $hall->delete();
        return $this->sendSuccess('Hall deleted successfully.');
    }

    // -------------------- Tables --------------------
    public function tables(Request $request): JsonResponse
    {
        $query = RestaurantTable::query()->with(['hall:id,name']);
        foreach (['store_id', 'shop_id', 'hall_id', 'state'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->get($f));
            }
        }
        return $this->sendResponse(
            $query->orderBy('hall_id')->orderBy('name')->get(),
            'Tables retrieved successfully.'
        );
    }

    public function storeTable(Request $request): JsonResponse
    {
        $input = $this->validateTable($request);
        $table = RestaurantTable::create($input);
        return $this->sendResponse($table->load('hall:id,name'), 'Table created successfully.');
    }

    public function updateTable(Request $request, RestaurantTable $table): JsonResponse
    {
        $input = $this->validateTable($request);
        $table->update($input);
        return $this->sendResponse($table->fresh()->load('hall:id,name'), 'Table updated successfully.');
    }

    public function destroyTable(RestaurantTable $table): JsonResponse
    {
        $table->delete();
        return $this->sendSuccess('Table deleted successfully.');
    }

    public function changeTableState(Request $request, RestaurantTable $table): JsonResponse
    {
        $input = $request->validate([
            'state' => ['required', Rule::in([
                RestaurantTable::STATE_FREE,
                RestaurantTable::STATE_OCCUPIED,
                RestaurantTable::STATE_RESERVED,
                RestaurantTable::STATE_BILLED,
            ])],
        ]);
        $table->update(['state' => $input['state']]);
        return $this->sendResponse($table, 'Table state updated.');
    }

    private function validateTable(Request $request): array
    {
        return $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'shop_id' => 'nullable|exists:shops,id',
            'hall_id' => 'nullable|exists:restaurant_halls,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'seats' => 'nullable|integer|min:1',
            'state' => ['nullable', Rule::in([
                RestaurantTable::STATE_FREE,
                RestaurantTable::STATE_OCCUPIED,
                RestaurantTable::STATE_RESERVED,
                RestaurantTable::STATE_BILLED,
            ])],
            'status' => 'nullable|boolean',
        ]);
    }

    // -------------------- KOT --------------------
    public function tickets(Request $request): JsonResponse
    {
        $query = KitchenOrderTicket::query()->with(['items', 'table:id,name', 'waiter:id,first_name,last_name']);
        foreach (['shop_id', 'status', 'table_id', 'order_type', 'kitchen_id'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->get($f));
            }
        }

        // A kitchen user only sees KOTs routed to their kitchen.
        $user = $request->user();
        if ($user && $user->hasRole(\App\Models\Role::KITCHEN) && $user->kitchen_id) {
            $query->where('kitchen_id', $user->kitchen_id);
        }
        return $this->sendResponse(
            $query->orderByDesc('id')->paginate(getPageSize($request)),
            'Kitchen tickets retrieved successfully.'
        );
    }

    public function storeTicket(Request $request): JsonResponse
    {
        $input = $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'shop_id' => 'nullable|exists:shops,id',
            'table_id' => 'nullable|exists:restaurant_tables,id',
            'waiter_id' => 'nullable|exists:users,id',
            'order_type' => ['nullable', Rule::in(['dine_in', 'takeaway', 'delivery'])],
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required_without:items.*.product_id|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.modifier' => 'nullable|string|max:1000',
        ]);

        // Default the waiter to the logged-in user (the one sending the order).
        $input['waiter_id'] = $input['waiter_id'] ?? $request->user()?->id;

        // Route the KOT to the waiter's assigned kitchen (fallback: the table's
        // hall kitchen). This is the kitchen whose display the order shows on.
        $kitchenId = null;
        if (! empty($input['waiter_id'])) {
            $kitchenId = \App\Models\User::withoutGlobalScope('tenant')
                ->where('id', $input['waiter_id'])->value('kitchen_id');
        }
        if (! $kitchenId && ! empty($input['table_id'])) {
            $hallId = RestaurantTable::where('id', $input['table_id'])->value('hall_id');
            if ($hallId) {
                $kitchenId = RestaurantHall::where('id', $hallId)->value('kitchen_id');
            }
        }

        return DB::transaction(function () use ($input, $kitchenId) {
            $ticket = KitchenOrderTicket::create([
                'store_id' => $input['store_id'] ?? null,
                'shop_id' => $input['shop_id'] ?? null,
                'kitchen_id' => $kitchenId,
                'table_id' => $input['table_id'] ?? null,
                'waiter_id' => $input['waiter_id'] ?? null,
                'order_type' => $input['order_type'] ?? 'dine_in',
                'ticket_no' => 'KOT-' . strtoupper(Str::random(6)),
                'status' => KitchenOrderTicket::STATUS_OPEN,
                'note' => $input['note'] ?? null,
            ]);

            foreach ($input['items'] as $row) {
                KitchenOrderItem::create([
                    'ticket_id' => $ticket->id,
                    'product_id' => $row['product_id'] ?? null,
                    'product_name' => $row['product_name'] ?? null,
                    'quantity' => $row['quantity'],
                    'modifier' => $row['modifier'] ?? null,
                    'status' => 'pending',
                ]);
            }

            if ($ticket->table_id) {
                RestaurantTable::where('id', $ticket->table_id)
                    ->update(['state' => RestaurantTable::STATE_OCCUPIED]);
            }

            return $this->sendResponse($ticket->load('items'), 'KOT created successfully.');
        });
    }

    public function updateTicketStatus(Request $request, KitchenOrderTicket $ticket): JsonResponse
    {
        $input = $request->validate([
            'status' => ['required', Rule::in([
                KitchenOrderTicket::STATUS_OPEN,
                KitchenOrderTicket::STATUS_SENT,
                KitchenOrderTicket::STATUS_IN_PROGRESS,
                KitchenOrderTicket::STATUS_READY,
                KitchenOrderTicket::STATUS_SERVED,
                KitchenOrderTicket::STATUS_CANCELLED,
            ])],
        ]);

        $updates = ['status' => $input['status']];
        if ($input['status'] === KitchenOrderTicket::STATUS_SENT && !$ticket->sent_to_kitchen_at) {
            $updates['sent_to_kitchen_at'] = now();
        }
        if ($input['status'] === KitchenOrderTicket::STATUS_READY) {
            $updates['ready_at'] = now();
        }
        if ($input['status'] === KitchenOrderTicket::STATUS_SERVED) {
            $updates['served_at'] = now();
        }

        $ticket->update($updates);

        if ($input['status'] === KitchenOrderTicket::STATUS_SERVED && $ticket->table_id) {
            RestaurantTable::where('id', $ticket->table_id)
                ->update(['state' => RestaurantTable::STATE_FREE]);
        }

        return $this->sendResponse($ticket->refresh()->load('items'), 'KOT status updated.');
    }

    // -------------------- Kitchens --------------------
    public function kitchens(Request $request): JsonResponse
    {
        $query = \App\Models\Kitchen::query()->with('store:id,name');
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->get('store_id'));
        }
        return $this->sendResponse(
            $query->orderBy('name')->get(),
            'Kitchens retrieved successfully.'
        );
    }

    public function storeKitchen(Request $request): JsonResponse
    {
        $input = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'name'     => 'required|string|max:255',
            'status'   => 'nullable|boolean',
        ]);
        $kitchen = \App\Models\Kitchen::create($input);
        return $this->sendResponse($kitchen, 'Kitchen created successfully.');
    }

    public function updateKitchen(Request $request, \App\Models\Kitchen $kitchen): JsonResponse
    {
        $input = $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'name'     => 'required|string|max:255',
            'status'   => 'nullable|boolean',
        ]);
        $kitchen->update($input);
        return $this->sendResponse($kitchen->refresh(), 'Kitchen updated successfully.');
    }

    public function destroyKitchen(\App\Models\Kitchen $kitchen): JsonResponse
    {
        // Unlink waiters/halls pointing at this kitchen, then delete.
        \App\Models\User::withoutGlobalScope('tenant')->where('kitchen_id', $kitchen->id)->update(['kitchen_id' => null]);
        RestaurantHall::where('kitchen_id', $kitchen->id)->update(['kitchen_id' => null]);
        $kitchen->delete();
        return $this->sendSuccess('Kitchen deleted successfully.');
    }

    /**
     * Assign (or clear) a waiter's kitchen — KOTs they send route there.
     */
    public function assignWaiterKitchen(Request $request): JsonResponse
    {
        $input = $request->validate([
            'waiter_id'  => 'required|exists:users,id',
            'kitchen_id' => 'nullable|exists:kitchens,id',
        ]);

        \App\Models\User::withoutGlobalScope('tenant')
            ->where('id', $input['waiter_id'])
            ->update(['kitchen_id' => $input['kitchen_id'] ?? null]);

        return $this->sendSuccess('Waiter kitchen updated.');
    }
}
