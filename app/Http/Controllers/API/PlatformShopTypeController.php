<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\ShopType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Super-admin management of the shop-type registry (enable/disable/add).
 */
class PlatformShopTypeController extends AppBaseController
{
    public function index(): JsonResponse
    {
        return $this->sendResponse(
            ShopType::query()->orderBy('sort_order')->orderBy('label')->get(),
            'Shop types retrieved successfully.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label'   => 'required|string|max:120',
            'enabled' => 'nullable|boolean',
        ]);

        $type = ShopType::create([
            'key'     => Str::slug($data['label'], '_'),
            'label'   => $data['label'],
            'enabled' => $data['enabled'] ?? true,
        ]);

        return $this->sendResponse($type, 'Shop type created successfully.');
    }

    public function update(Request $request, ShopType $shopType): JsonResponse
    {
        $data = $request->validate([
            'label'   => 'nullable|string|max:120',
            'enabled' => 'nullable|boolean',
        ]);

        $shopType->update(array_filter([
            'label'   => $data['label'] ?? null,
        ], fn ($v) => $v !== null) + (
            array_key_exists('enabled', $data) ? ['enabled' => (bool) $data['enabled']] : []
        ));

        return $this->sendResponse($shopType->fresh(), 'Shop type updated successfully.');
    }
}
