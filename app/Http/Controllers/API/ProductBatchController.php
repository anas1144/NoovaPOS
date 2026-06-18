<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\ProductBatch;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Product batch + expiry management (Pharmacy / perishable shop types).
 */
class ProductBatchController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $rows = ProductBatch::query()
            ->with('product:id,name,code')
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->get('product_id')))
            ->when($request->filled('search'), fn ($q) => $q->where('batch_no', 'like', '%' . $request->get('search') . '%'))
            ->orderBy('expiry_date')
            ->paginate((int) $request->get('page_size', 25));

        return $this->sendResponse($rows, 'Batches retrieved.');
    }

    /**
     * Near-expiry + expired batches (default within 60 days). For the alert
     * widget and the Pharmacy dashboard.
     */
    public function nearExpiry(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 60);
        $cutoff = Carbon::today()->addDays($days);

        $rows = ProductBatch::query()
            ->with('product:id,name,code')
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $cutoff)
            ->orderBy('expiry_date')
            ->get()
            ->map(fn ($b) => [
                'id'          => $b->id,
                'product'     => $b->product?->name,
                'code'        => $b->product?->code,
                'batch_no'    => $b->batch_no,
                'expiry_date' => $b->expiry_date?->toDateString(),
                'days_left'   => $b->expiry_date ? Carbon::today()->diffInDays($b->expiry_date, false) : null,
                'quantity'    => $b->quantity,
                'expired'     => $b->expiry_date && $b->expiry_date->isPast(),
            ]);

        return $this->sendResponse($rows, 'Near-expiry batches retrieved.');
    }

    public function store(Request $request): JsonResponse
    {
        return $this->persist(new ProductBatch(), $request, 'Batch created.');
    }

    public function update(Request $request, ProductBatch $productBatch): JsonResponse
    {
        return $this->persist($productBatch, $request, 'Batch updated.');
    }

    public function destroy(ProductBatch $productBatch): JsonResponse
    {
        $productBatch->delete();
        return $this->sendSuccess('Batch removed.');
    }

    private function persist(ProductBatch $batch, Request $request, string $message): JsonResponse
    {
        $data = $request->validate([
            'product_id'  => 'required|integer',
            'store_id'    => 'nullable|integer',
            'batch_no'    => 'required|string|max:80',
            'mfg_date'    => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'quantity'    => 'required|numeric|min:0',
            'cost'        => 'nullable|numeric|min:0',
            'status'      => 'nullable|in:active,quarantined,expired',
        ]);

        $batch->fill($data)->save();

        return $this->sendResponse($batch->load('product:id,name,code'), $message);
    }
}
