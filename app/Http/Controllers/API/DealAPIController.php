<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\Deal;
use App\Models\DealItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Deals / combos management (tenant).
 */
class DealAPIController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Deal::query()->with('items.product:id,name,product_price');
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->get('store_id'));
        }
        return $this->sendResponse($query->orderBy('name')->get(), 'Deals retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        return $this->persist(new Deal(), $data, 'Deal created successfully.');
    }

    public function update(Request $request, Deal $deal): JsonResponse
    {
        $data = $this->validatePayload($request);
        return $this->persist($deal, $data, 'Deal updated successfully.');
    }

    public function destroy(Deal $deal): JsonResponse
    {
        DealItem::where('deal_id', $deal->id)->delete();
        $deal->delete();
        return $this->sendSuccess('Deal deleted successfully.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'store_id'           => 'nullable|integer',
            'name'               => 'required|string|max:191',
            'code'               => 'nullable|string|max:60',
            'price'              => 'required|numeric|min:0',
            'status'             => 'nullable|boolean',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity'   => 'required|numeric|min:0.01',
        ]);
    }

    private function persist(Deal $deal, array $data, string $message): JsonResponse
    {
        return DB::transaction(function () use ($deal, $data, $message) {
            $deal->fill([
                'store_id' => $data['store_id'] ?? null,
                'name'     => $data['name'],
                'code'     => $data['code'] ?? null,
                'price'    => $data['price'],
                'status'   => $data['status'] ?? true,
            ])->save();

            DealItem::where('deal_id', $deal->id)->delete();
            foreach ($data['items'] as $row) {
                DealItem::create([
                    'deal_id'    => $deal->id,
                    'product_id' => $row['product_id'],
                    'quantity'   => $row['quantity'],
                ]);
            }

            return $this->sendResponse($deal->load('items.product:id,name'), $message);
        });
    }
}
