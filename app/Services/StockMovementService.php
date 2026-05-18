<?php

namespace App\Services;

use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;

class StockMovementService
{
    public function logIn(
        string $movementType,
        int $warehouseId,
        int $productId,
        float $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $tenantId = null,
        ?int $storeId = null,
        ?int $shopId = null
    ): void {
        $this->log(
            movementType: $movementType,
            direction: StockMovement::DIRECTION_IN,
            warehouseId: $warehouseId,
            productId: $productId,
            quantity: $quantity,
            referenceType: $referenceType,
            referenceId: $referenceId,
            tenantId: $tenantId,
            storeId: $storeId,
            shopId: $shopId
        );
    }

    public function logOut(
        string $movementType,
        int $warehouseId,
        int $productId,
        float $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $tenantId = null,
        ?int $storeId = null,
        ?int $shopId = null
    ): void {
        $this->log(
            movementType: $movementType,
            direction: StockMovement::DIRECTION_OUT,
            warehouseId: $warehouseId,
            productId: $productId,
            quantity: $quantity,
            referenceType: $referenceType,
            referenceId: $referenceId,
            tenantId: $tenantId,
            storeId: $storeId,
            shopId: $shopId
        );
    }

    private function log(
        string $movementType,
        string $direction,
        int $warehouseId,
        int $productId,
        float $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $tenantId = null,
        ?int $storeId = null,
        ?int $shopId = null
    ): void {
        if ($quantity <= 0) {
            return;
        }

        StockMovement::create([
            'tenant_id' => $tenantId,
            'store_id' => $storeId,
            'shop_id' => $shopId,
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'movement_type' => $movementType,
            'direction' => $direction,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => Auth::id(),
        ]);
    }
}
