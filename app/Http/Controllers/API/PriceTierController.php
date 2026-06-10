<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\PriceTier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Tenant management of price tiers. Seeds defaults (retail/wholesale/m20) on
 * first access so a tenant always has at least the retail (default) tier.
 */
class PriceTierController extends AppBaseController
{
    public function index(): JsonResponse
    {
        $tiers = PriceTier::query()->orderBy('sort_order')->get();

        if ($tiers->isEmpty()) {
            foreach ([['retail', 'Retail', true, 0], ['wholesale', 'Wholesale', false, 1], ['m20', 'M20', false, 2]] as [$k, $l, $d, $o]) {
                PriceTier::create(['key' => $k, 'label' => $l, 'is_default' => $d, 'sort_order' => $o]);
            }
            $tiers = PriceTier::query()->orderBy('sort_order')->get();
        }

        return $this->sendResponse($tiers, 'Price tiers retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label' => 'required|string|max:60',
        ]);

        $tier = PriceTier::create([
            'key'        => Str::slug($data['label'], '_'),
            'label'      => $data['label'],
            'is_default' => false,
            'sort_order' => (int) (PriceTier::max('sort_order') + 1),
        ]);

        return $this->sendResponse($tier, 'Price tier created successfully.');
    }

    public function update(Request $request, PriceTier $priceTier): JsonResponse
    {
        $data = $request->validate(['label' => 'required|string|max:60']);
        $priceTier->update(['label' => $data['label']]);

        return $this->sendResponse($priceTier->refresh(), 'Price tier updated successfully.');
    }

    public function destroy(PriceTier $priceTier): JsonResponse
    {
        if ($priceTier->is_default) {
            return $this->sendError('The default (retail) tier cannot be deleted.', 422);
        }
        $priceTier->delete();

        return $this->sendSuccess('Price tier deleted successfully.');
    }
}
