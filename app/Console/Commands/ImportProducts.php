<?php

namespace App\Console\Commands;

use App\Models\BaseUnit;
use App\Models\Brand;
use App\Models\MainProduct;
use App\Models\ManageStock;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ImportProducts extends Command
{
    protected $signature = 'import:products {path?}';
    protected $description = 'Import products from CSV. Matches by Code - creates new or updates existing with stocks.';

    public function handle()
    {
        $path = $this->argument('path') ?? base_path('import_csv/import_products.csv');

        if (!file_exists($path)) {
            $this->error("File not found: $path");
            return 1;
        }

        $this->info("Starting product import from: $path");

        $fh = fopen($path, 'r');
        if (!$fh) {
            $this->error('Unable to open file');
            return 1;
        }

        // Skip header row
        fgetcsv($fh);

        $rowNum = 2;
        $successCount = 0;
        $errorCount = 0;

        while (($row = fgetcsv($fh)) !== false) {
            try {
                DB::beginTransaction();

                // Parse CSV columns
                $name = $row[0] ?? null;
                $code = $row[1] ?? null;
                $category = $row[2] ?? null;
                $brand = $row[3] ?? null;
                $barcodeSymbol = $row[4] ?? null;
                $productCost = $row[5] ?? null;
                $productPrice = $row[6] ?? null;
                $productUnit = $row[7] ?? null;
                $saleUnit = $row[8] ?? null;
                $purchaseUnit = $row[9] ?? null;
                $stockAlert = $row[10] ?? null;
                $orderTax = $row[11] ?? null;
                $taxType = $row[12] ?? null;
                $notes = $row[13] ?? null;
                $warehouseName = $row[14] ?? null;
                $supplierName = $row[15] ?? null;
                $quantity = $row[16] ?? null;
                $status = $row[17] ?? null;
                $variationName = $row[18] ?? null;
                $variationTypeName = $row[19] ?? null;

                // Validation
                if (empty($code)) {
                    throw new \Exception("Row $rowNum: Code is required");
                }

                if (empty($name)) {
                    throw new \Exception("Row $rowNum: Name is required");
                }

                // Check if product exists by Code
                $existingProduct = Product::where('code', $code)->first();
                $isUpdate = (bool)$existingProduct;

                // Get or create category
                $categoryModel = ProductCategory::whereName($category)->first();
                if (!$categoryModel && $category) {
                    $categoryModel = ProductCategory::create(['name' => $category]);
                }
                $categoryId = $categoryModel->id ?? null;

                // Get or create brand
                $brandModel = Brand::whereName($brand)->first();
                if (!$brandModel && $brand) {
                    $brandModel = Brand::create(['name' => $brand]);
                }
                $brandId = $brandModel->id ?? null;

                // Get base unit
                $baseUnitModel = BaseUnit::whereName(strtolower($productUnit))->first();
                if (!$baseUnitModel) {
                    throw new \Exception("Row $rowNum: Product unit '$productUnit' not found");
                }
                $productUnitId = $baseUnitModel->id;

                // Get sale unit
                $saleUnitModel = Unit::whereName(strtolower($saleUnit))
                    ->whereBaseUnit($productUnitId)->first();
                if (!$saleUnitModel) {
                    throw new \Exception("Row $rowNum: Sale unit '$saleUnit' not found");
                }

                // Get purchase unit
                $purchaseUnitModel = Unit::whereName(strtolower($purchaseUnit))
                    ->whereBaseUnit($productUnitId)->first();
                if (!$purchaseUnitModel) {
                    throw new \Exception("Row $rowNum: Purchase unit '$purchaseUnit' not found");
                }

                // Convert tax type
                $taxTypeId = null;
                if (strtolower($taxType) == 'exclusive') {
                    $taxTypeId = 1;
                } elseif (strtolower($taxType) == 'inclusive') {
                    $taxTypeId = 2;
                } else {
                    throw new \Exception("Row $rowNum: Invalid tax type '$taxType'");
                }

                // Convert barcode symbol
                $barcodeSymbolId = null;
                if ($barcodeSymbol == 'CODE128') {
                    $barcodeSymbolId = 1;
                } elseif ($barcodeSymbol == 'CODE39') {
                    $barcodeSymbolId = 2;
                } else {
                    throw new \Exception("Row $rowNum: Invalid barcode symbol '$barcodeSymbol'");
                }

                // Get or create main product
                $mainProduct = MainProduct::where('code', $code)->first();
                if (!$mainProduct) {
                    $mainProduct = MainProduct::create([
                        'name' => $name,
                        'code' => $code,
                        'product_unit' => $productUnitId,
                        'product_type' => 1, // SINGLE_PRODUCT
                    ]);
                }

                // Prepare product data
                $productData = [
                    'name' => $name,
                    'code' => $code,
                    'product_code' => $code,
                    'product_category_id' => $categoryId,
                    'brand_id' => $brandId,
                    'barcode_symbol' => $barcodeSymbolId,
                    'product_cost' => $productCost ?? 0,
                    'product_price' => $productPrice ?? 0,
                    'product_unit' => $productUnitId,
                    'sale_unit' => $saleUnitModel->id,
                    'purchase_unit' => $purchaseUnitModel->id,
                    'stock_alert' => $stockAlert,
                    'order_tax' => $orderTax ?? 0,
                    'tax_type' => $taxTypeId,
                    'notes' => $notes,
                    'main_product_id' => $mainProduct->id,
                ];

                if ($isUpdate) {
                    // Update existing product
                    $product = $existingProduct;
                    $product->update($productData);
                    $this->info("Row $rowNum: Updated product '$code' (ID: {$product->id})");
                } else {
                    // Create new product
                    $product = Product::create($productData);
                    $this->info("Row $rowNum: Created product '$code' (ID: {$product->id})");
                }

                // Handle stock
                if (!empty($warehouseName)) {
                    // Set quantity to 0 if empty or null
                    $quantity = $quantity ?? 0;
                    if (empty($quantity)) {
                        $quantity = 0;
                    }
                    
                    $warehouse = Warehouse::whereRaw('LOWER(name) = ?', [strtolower($warehouseName)])->first();
                    if (!$warehouse) {
                        throw new \Exception("Row $rowNum: Warehouse '$warehouseName' not found");
                    }

                    // Replace stock with CSV quantity value (not add)
                    $stock = ManageStock::where('warehouse_id', $warehouse->id)
                        ->where('product_id', $product->id)
                        ->first();

                    if ($stock) {
                        $stock->update(['quantity' => $quantity]);
                        $this->line("  └─ Replaced stock in warehouse '$warehouseName': $quantity units");
                    } else {
                        ManageStock::create([
                            'warehouse_id' => $warehouse->id,
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                        ]);
                        $this->line("  └─ Created stock in warehouse '$warehouseName': $quantity units");
                    }

                    // Handle purchase record only for new products
                    if (!$isUpdate && !empty($supplierName)) {
                        $supplier = Supplier::whereRaw('LOWER(name) = ?', [strtolower($supplierName)])->first();
                        if (!$supplier) {
                            // Auto-create supplier if it doesn't exist
                            $supplier = Supplier::create(['name' => $supplierName]);
                            $this->line("  └─ Auto-created supplier: '$supplierName'");
                        }

                        $statusId = 1; // received by default
                        if (strtolower($status) == 'ordered') {
                            $statusId = 3;
                        } elseif (strtolower($status) == 'pending') {
                            $statusId = 2;
                        }

                        $purchase = Purchase::create([
                            'supplier_id' => $supplier->id,
                            'warehouse_id' => $warehouse->id,
                            'date' => Carbon::now()->format('Y-m-d'),
                            'status' => $statusId,
                        ]);

                        PurchaseItem::create([
                            'purchase_id' => $purchase->id,
                            'product_id' => $product->id,
                            'product_cost' => $productCost ?? 0,
                            'net_unit_cost' => $productCost ?? 0,
                            'tax_type' => $taxTypeId,
                            'tax_value' => $orderTax ?? 0,
                            'tax_amount' => 0,
                            'discount_type' => 1,
                            'discount_value' => 0,
                            'discount_amount' => 0,
                            'purchase_unit' => $purchaseUnitModel->id,
                            'quantity' => $quantity,
                            'sub_total' => ($productCost ?? 0) * $quantity,
                        ]);

                        $purchase->update([
                            'reference_code' => 'PO_' . $purchase->id,
                            'grand_total' => ($productCost ?? 0) * $quantity,
                        ]);
                    }
                }

                // Generate barcode
                try {
                    $generator = new BarcodeGeneratorPNG();
                    $barcodeType = ($barcodeSymbolId == 1) ? $generator::TYPE_CODE_128 : $generator::TYPE_CODE_39;
                    $barcodeData = $generator->getBarcode($code, $barcodeType, 4, 70);
                    
                    \Illuminate\Support\Facades\Storage::disk(config('app.media_disc'))->put(
                        'product_barcode/barcode-PR_' . $product->id . '.png',
                        $barcodeData
                    );
                } catch (\Exception $e) {
                    $this->warn("  └─ Could not generate barcode: " . $e->getMessage());
                }

                DB::commit();
                $successCount++;

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Row $rowNum Error: " . $e->getMessage());
                $errorCount++;
            }

            $rowNum++;
        }

        fclose($fh);

        $this->line('');
        $this->info("Import completed!");
        $this->line("Success: $successCount | Errors: $errorCount");

        return 0;
    }
}

