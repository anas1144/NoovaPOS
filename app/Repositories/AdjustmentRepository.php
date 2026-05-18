<?php

namespace App\Repositories;

use App\Models\Adjustment;
use App\Models\AdjustmentItem;
use App\Models\ManageStock;
use App\Services\StockMovementService;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class SaleRepository
 */
class AdjustmentRepository extends BaseRepository
{
    private ?StockMovementService $stockMovementService = null;

    private function stockMovementService(): StockMovementService
    {
        return $this->stockMovementService ??= app(StockMovementService::class);
    }
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'date',
        'reference_code',
        'warehouse_id',
        'total_products',
        'created_at',
    ];

    /**
     * @var string[]
     */
    protected $allowedFields = [
        'date',
    ];

    /**
     * Return searchable fields
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Adjustment::class;
    }

    public function storeAdjustment($input): Adjustment
    {
        try {
            DB::beginTransaction();

            $input['total_products'] = count($input['adjustment_items']);
            $input['date'] = $input['date'] ?? date('Y/m/d');
            $input['posted_status'] = Adjustment::STATUS_DRAFT; // Always create as draft
            $adjustmentInputArray = Arr::only($input, [
                'date', 'warehouse_id', 'total_products', 'posted_status',
            ]);
            $adjustment = Adjustment::create($adjustmentInputArray);
            $reference_code = 'AD_111'.$adjustment->id;
            $adjustment->update(['reference_code' => $reference_code]);

            $adjustment = $this->storeAdjustmentItems($adjustment, $input);

            DB::commit();

            return $adjustment;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function storeAdjustmentItems($adjustment, $input)
    {
        foreach ($input['adjustment_items'] as $adjustmentItem) {
            $adjustmentItem['adjustment_id'] = $adjustment->id;
            AdjustmentItem::Create($adjustmentItem);
            
            // Stock will be updated when adjustment is posted, not when saved as draft
        }

        return $adjustment;
    }

    public function updateAdjustment($input, $id)
    {
        try {
            DB::beginTransaction();

            $adjustment = Adjustment::findOrFail($id);

            $input['total_products'] = count($input['adjustment_items']);
            $input['date'] = $input['date'] ?? date('Y/m/d');
            $adjustmentInputArray = Arr::only($input, [
                'date', 'warehouse_id', 'total_products',
            ]);
            $adjustment->update($adjustmentInputArray);

            $adjustment = $this->updateAdjustmentItems($adjustment, $input);

            DB::commit();

            return $adjustment;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function updateAdjustmentItems($adjustment, $input)
    {
        $adjustmentItmOldIds = AdjustmentItem::whereAdjustmentId($adjustment->id)->pluck('id')->toArray();
        $adjustmentItemIds = [];

        foreach ($input['adjustment_items'] as $key => $adjustmentItem) {
            $adjustmentItemIds[$key] = $adjustmentItem['adjustment_item_id'] ?? null;

            if (is_null($adjustmentItem['adjustment_item_id'])) {
                $adjustmentItem['adjustment_id'] = $adjustment->id;
                AdjustmentItem::Create($adjustmentItem);
            } else {
                $exitAdjustmentItem = AdjustmentItem::whereId($adjustmentItem['adjustment_item_id'])->firstOrFail();
                $exitAdjustmentItem->update([
                    'quantity' => $adjustmentItem['quantity'],
                    'method_type' => $adjustmentItem['method_type'],
                ]);
            }
        }

        $removeItemIds = array_diff($adjustmentItmOldIds, $adjustmentItemIds);

        if (! empty(array_values($removeItemIds))) {
            AdjustmentItem::whereIn('id', array_values($removeItemIds))->delete();
        }

        return $adjustment;
    }

    /**
     * Post an adjustment - Apply stock changes and mark as posted
     */
    public function postAdjustment($id): Adjustment
    {
        try {
            DB::beginTransaction();

            $adjustment = Adjustment::with('adjustmentItems')->findOrFail($id);

            // Check if already posted
            if ($adjustment->posted_status == Adjustment::STATUS_POSTED) {
                throw new UnprocessableEntityHttpException('This adjustment has already been posted.');
            }

            // Apply all stock changes
            foreach ($adjustment->adjustmentItems as $adjustmentItem) {
                if ($adjustmentItem->method_type == AdjustmentItem::METHOD_ADDITION) {
                    // Add stock
                    manageStock($adjustment->warehouse_id, $adjustmentItem->product_id, $adjustmentItem->quantity);
                    $this->stockMovementService()->logIn(
                        movementType: 'adjustment',
                        warehouseId: (int) $adjustment->warehouse_id,
                        productId: (int) $adjustmentItem->product_id,
                        quantity: (float) $adjustmentItem->quantity,
                        referenceType: Adjustment::class,
                        referenceId: (int) $adjustment->id,
                        tenantId: $adjustment->tenant_id
                    );
                } else {
                    // Check if we have enough stock before deduction
                    $product = ManageStock::whereWarehouseId($adjustment->warehouse_id)
                        ->whereProductId($adjustmentItem->product_id)
                        ->first();

                    if (!$product || $product->quantity < $adjustmentItem->quantity) {
                        throw new UnprocessableEntityHttpException('Quantity exceeds quantity available in stock.');
                    }

                    // Deduct stock using negative quantity
                    manageStock($adjustment->warehouse_id, $adjustmentItem->product_id, -$adjustmentItem->quantity);
                    $this->stockMovementService()->logOut(
                        movementType: 'adjustment',
                        warehouseId: (int) $adjustment->warehouse_id,
                        productId: (int) $adjustmentItem->product_id,
                        quantity: (float) $adjustmentItem->quantity,
                        referenceType: Adjustment::class,
                        referenceId: (int) $adjustment->id,
                        tenantId: $adjustment->tenant_id
                    );
                }
            }

            // Mark as posted
            $adjustment->update(['posted_status' => Adjustment::STATUS_POSTED]);

            DB::commit();

            return $adjustment;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}
