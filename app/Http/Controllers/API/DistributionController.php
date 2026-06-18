<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\DeliveryRoute;
use App\Models\DispatchLoad;
use App\Models\DispatchLoadItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Distribution: van load-out (dispatch) + reconciliation (delivered vs returned),
 * over the shared delivery_routes.
 */
class DistributionController extends AppBaseController
{
    /** Routes available for dispatch (reuses the shared delivery_routes). */
    public function routes(Request $request): JsonResponse
    {
        return $this->sendResponse(
            DeliveryRoute::query()->where('status', true)->orderBy('name')->get(),
            'Routes retrieved.'
        );
    }

    public function loads(Request $request): JsonResponse
    {
        $rows = DispatchLoad::query()
            ->with(['route:id,name', 'items'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->orderByDesc('id')
            ->paginate((int) $request->get('page_size', 20));

        return $this->sendResponse($rows, 'Loads retrieved.');
    }

    public function storeLoad(Request $request): JsonResponse
    {
        $data = $request->validate([
            'delivery_route_id'  => 'nullable|integer',
            'driver_user_id'     => 'nullable|integer',
            'date'               => 'required|date',
            'vehicle'            => 'nullable|string|max:60',
            'note'               => 'nullable|string|max:191',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.loaded_qty' => 'required|numeric|min:0',
        ]);

        $load = DB::transaction(function () use ($data, $request) {
            $load = DispatchLoad::create([
                'delivery_route_id' => $data['delivery_route_id'] ?? null,
                'driver_user_id'    => $data['driver_user_id'] ?? null,
                'date'              => $data['date'],
                'vehicle'           => $data['vehicle'] ?? null,
                'note'              => $data['note'] ?? null,
                'status'            => 'loaded',
                'created_by'        => $request->user()?->id,
            ]);

            foreach ($data['items'] as $it) {
                DispatchLoadItem::create([
                    'dispatch_load_id' => $load->id,
                    'product_id'       => $it['product_id'],
                    'loaded_qty'       => $it['loaded_qty'],
                ]);
            }

            return $load;
        });

        return $this->sendResponse($load->load(['route:id,name', 'items']), 'Load created.');
    }

    /** Reconcile a load: record delivered/returned per item → status reconciled. */
    public function reconcile(Request $request, DispatchLoad $dispatchLoad): JsonResponse
    {
        $data = $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.id'           => 'required|integer',
            'items.*.delivered_qty' => 'required|numeric|min:0',
            'items.*.returned_qty' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($data, $dispatchLoad) {
            foreach ($data['items'] as $row) {
                DispatchLoadItem::query()
                    ->where('id', $row['id'])
                    ->where('dispatch_load_id', $dispatchLoad->id)
                    ->update([
                        'delivered_qty' => $row['delivered_qty'],
                        'returned_qty'  => $row['returned_qty'],
                    ]);
            }
            $dispatchLoad->update(['status' => 'reconciled']);
        });

        return $this->sendResponse($dispatchLoad->load(['route:id,name', 'items']), 'Load reconciled.');
    }

    public function destroyLoad(DispatchLoad $dispatchLoad): JsonResponse
    {
        DispatchLoadItem::query()->where('dispatch_load_id', $dispatchLoad->id)->delete();
        $dispatchLoad->delete();
        return $this->sendSuccess('Load removed.');
    }
}
