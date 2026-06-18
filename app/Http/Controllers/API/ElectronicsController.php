<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\ProductSerial;
use App\Models\Warranty;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Electronics: serial-number tracking + warranty registration & lookup.
 */
class ElectronicsController extends AppBaseController
{
    // ── Serials ────────────────────────────────────────────────────────────────

    public function serials(Request $request): JsonResponse
    {
        $rows = ProductSerial::query()->with('product:id,name,code')
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->get('product_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('serial_no', 'like', '%' . $request->get('search') . '%'))
            ->orderByDesc('id')->paginate((int) $request->get('page_size', 25));

        return $this->sendResponse($rows, 'Serials retrieved.');
    }

    /** Add one or many serials for a product (newline/comma separated). */
    public function storeSerials(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|integer',
            'store_id'   => 'nullable|integer',
            'serials'    => 'required|string',
        ]);

        $list = preg_split('/[\r\n,]+/', $data['serials'], -1, PREG_SPLIT_NO_EMPTY);
        $added = 0;
        foreach (array_unique(array_map('trim', $list)) as $serial) {
            if ($serial === '') {
                continue;
            }
            $exists = ProductSerial::query()->where('product_id', $data['product_id'])->where('serial_no', $serial)->exists();
            if ($exists) {
                continue;
            }
            ProductSerial::create([
                'product_id' => $data['product_id'],
                'store_id'   => $data['store_id'] ?? null,
                'serial_no'  => $serial,
                'status'     => 'in_stock',
            ]);
            $added++;
        }

        return $this->sendResponse(['added' => $added], "{$added} serial(s) added.");
    }

    public function updateSerial(Request $request, ProductSerial $productSerial): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:in_stock,sold,returned,faulty',
            'note'   => 'nullable|string|max:191',
        ]);
        if ($data['status'] === 'sold' && ! $productSerial->sold_at) {
            $productSerial->sold_at = now();
        }
        $productSerial->fill($data)->save();
        return $this->sendResponse($productSerial, 'Serial updated.');
    }

    public function destroySerial(ProductSerial $productSerial): JsonResponse
    {
        $productSerial->delete();
        return $this->sendSuccess('Serial removed.');
    }

    // ── Warranties ────────────────────────────────────────────────────────────────

    public function warranties(Request $request): JsonResponse
    {
        $rows = Warranty::query()->with('product:id,name')
            ->orderByDesc('id')->paginate((int) $request->get('page_size', 25));
        return $this->sendResponse($rows, 'Warranties retrieved.');
    }

    public function registerWarranty(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id'  => 'required|integer',
            'serial_no'   => 'required|string|max:120',
            'customer_id' => 'nullable|integer',
            'start_date'  => 'required|date',
            'months'      => 'required|integer|min:1|max:120',
            'note'        => 'nullable|string|max:191',
        ]);

        $data['end_date'] = Carbon::parse($data['start_date'])->addMonths((int) $data['months'])->toDateString();
        $data['status'] = 'active';

        $warranty = Warranty::create($data);
        return $this->sendResponse($warranty->load('product:id,name'), 'Warranty registered.');
    }

    /** Public-ish lookup by serial — returns the warranty + active/expired state. */
    public function lookup(Request $request): JsonResponse
    {
        $serial = trim((string) $request->get('serial_no'));
        if ($serial === '') {
            return $this->sendError('Provide a serial number.', 422);
        }

        $warranty = Warranty::query()->with('product:id,name')
            ->where('serial_no', $serial)->latest('id')->first();

        if (! $warranty) {
            return $this->sendResponse(['found' => false], 'No warranty found for this serial.');
        }

        $active = $warranty->status === 'active' && Carbon::parse($warranty->end_date)->isFuture();

        return $this->sendResponse([
            'found'      => true,
            'product'    => $warranty->product?->name,
            'serial_no'  => $warranty->serial_no,
            'start_date' => $warranty->start_date?->toDateString(),
            'end_date'   => $warranty->end_date?->toDateString(),
            'months'     => $warranty->months,
            'active'     => $active,
            'days_left'  => $active ? Carbon::today()->diffInDays(Carbon::parse($warranty->end_date)) : 0,
        ], 'Warranty lookup result.');
    }
}
