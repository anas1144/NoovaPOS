<?php

namespace App\Http\Controllers\API;

use App\Exports\ProductExcelExport;
use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Imports\ProductImport;
use App\Models\MainProduct;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Models\VariationProduct;
use App\Repositories\ProductRepository;
use App\Services\TenantSubscriptionService;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\Cache;

class ProductAPIController extends AppBaseController
{
    /** @var ProductRepository */
    private $productRepository;

    public function __construct(
        ProductRepository $productRepository,
        private readonly TenantSubscriptionService $tenantSubscriptionService
    )
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Get cached file path for products
     */
    private function getProductCacheFilePath($warehouseId, $perPage)
    {
        return storage_path("app/cache/pos_products_w{$warehouseId}_s{$perPage}.json");
    }

    /**
     * Check if flat file cache exists and is fresh (within 5 minutes)
     */
    private function isCacheFresh($filePath)
    {
        if (!File::exists($filePath)) {
            return false;
        }
        
        $fileTime = File::lastModified($filePath);
        $cacheLifetime = 300; // 5 minutes in seconds
        
        return (time() - $fileTime) < $cacheLifetime;
    }

    /**
     * Clear product cache files
     */
    private function clearProductCache()
    {
        $cacheDir = storage_path('app/cache');
        if (File::isDirectory($cacheDir)) {
            $files = File::glob($cacheDir . '/pos_products_*.json');
            foreach ($files as $file) {
                File::delete($file);
            }
        }
        Cache::flush();
    }

    public function index(Request $request)
    {
        $perPage = getPageSize($request);
        $warehouseId = $request->get('warehouse_id');
        $productUnit = $request->get('product_unit');
        $page = $request->get('page', 1);
        
        // Ensure $perPage is a scalar value (handle array case)
        if (is_array($perPage)) {
            $perPage = $perPage['size'] ?? 10;
        }

        // For POS (page[size]=0), use flat file cache for maximum speed
        if ($perPage == 0 && $warehouseId) {
            $cacheFilePath = $this->getProductCacheFilePath($warehouseId, 0);
            
            // Try to load from flat file cache
            if ($this->isCacheFresh($cacheFilePath)) {
                $cachedData = json_decode(File::get($cacheFilePath), true);
                
                // Apply product unit filter if needed
                if ($productUnit) {
                    $cachedData['data'] = array_filter($cachedData['data'], function($product) use ($productUnit) {
                        return $product['attributes']['product_unit'] == $productUnit;
                    });
                    $cachedData['data'] = array_values($cachedData['data']); // Re-index array
                }
                
                return response()->json($cachedData);
            }
            
            // Cache miss - fetch from database and create cache file
            $products = $this->fetchProductsFromDatabase($warehouseId, $productUnit, $perPage);
            
            // Ensure cache directory exists
            $cacheDir = storage_path('app/cache');
            if (!File::isDirectory($cacheDir)) {
                File::makeDirectory($cacheDir, 0755, true);
            }
            
            // Convert to ProductCollection to get proper JSON:API format
            ProductResource::usingWithCollection();
            $productCollection = new ProductCollection($products);
            $formattedResponse = $productCollection->toResponse(request())->getData(true);
            
            // Save the properly formatted response to cache
            File::put($cacheFilePath, json_encode($formattedResponse));
            
            return response()->json($formattedResponse);
        }
        
        // For regular pagination, use Redis cache
        $cacheKey = sprintf(
            'products_list_w%s_u%s_p%s_s%s',
            $warehouseId ?? 'all',
            $productUnit ?? 'all',
            is_array($page) ? ($page['number'] ?? 1) : $page,
            $perPage
        );
        
        $products = Cache::remember($cacheKey, 300, function() use ($warehouseId, $productUnit, $perPage) {
            return $this->fetchProductsFromDatabase($warehouseId, $productUnit, $perPage);
        });
        
        ProductResource::usingWithCollection();
        return new ProductCollection($products);
    }

    /**
     * Fetch products from database
     */
    private function fetchProductsFromDatabase($warehouseId, $productUnit, $perPage)
    {
        $products = $this->productRepository;

        if ($productUnit) {
            $products->where('product_unit', $productUnit);
        }

        if ($warehouseId && $warehouseId != 'null') {
            $products->whereHas('stock', function ($q) use ($warehouseId) {
                $q->where('manage_stocks.warehouse_id', $warehouseId);
            })->with([
                'stock' => function (HasOne $query) use ($warehouseId) {
                    $query->where('manage_stocks.warehouse_id', $warehouseId);
                },
            ]);
        }

        // POS / sale load all products (page[size]=0). Hide products that exceed
        // the tenant's plan+add-on product limit (oldest within limit stay sellable).
        if ((int) $perPage === 0) {
            $cutoff = app(\App\Services\TenantSubscriptionService::class)
                ->productCutoff(\Illuminate\Support\Facades\Auth::user()?->tenant_id);
            if ($cutoff !== null) {
                $products->where('id', '<=', $cutoff);
            }
        }

        return $products->paginate($perPage);
    }

    public function store(CreateProductRequest $request)
    {
        $input = $request->all();
        $this->tenantSubscriptionService->assertWithinLimit(currentTenantId(), 'products');

        if ($input['main_product_id']) {
            $mainProduct = MainProduct::find($input['main_product_id']);
            if ($mainProduct->product_type == MainProduct::SINGLE_PRODUCT) {
                return $this->sendError('You can add variations for single type product');
            }
        }

        if ($input['barcode_symbol'] == Product::EAN8 && strlen($input['code']) != 7) {
            return $this->sendError('Please enter 7 digit code');
        }

        if ($input['barcode_symbol'] == Product::UPC && strlen($input['code']) != 11) {
            return $this->sendError(' Please enter 11 digit code');
        }

        $product = $this->productRepository->storeProduct($input);

        $this->syncTierPrices($product->id, $request->input('tier_prices'));
        $this->syncUnitLevels($product->id, $request->input('unit_levels'));

        VariationProduct::create([
            'product_id' => $product->id,
            'variation_id' => $input['variation_id'],
            'variation_type_id' => $input['variation_type'],
            'main_product_id' => $input['main_product_id'],
        ]);

        $this->clearProductCache();

        return new ProductResource($product);
    }

    public function show($id): ProductResource
    {
        $product = $this->productRepository->find($id);
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, $id): ProductResource
    {
        $input = $request->all();
        $product = $this->productRepository->updateProduct($input, $id);
        $this->syncTierPrices($id, $request->input('tier_prices'));
        $this->syncUnitLevels($id, $request->input('unit_levels'));
        $this->clearProductCache();
        return new ProductResource($product);
    }

    /**
     * Return a product's unit hierarchy (base-first), so the POS can offer a
     * box/pack/piece breakdown for a cart line.
     */
    public function unitLevels($id): \Illuminate\Http\JsonResponse
    {
        $levels = \App\Models\ProductUnitLevel::where('product_id', $id)
            ->orderBy('factor_to_base')
            ->get(['name', 'factor_to_base', 'sort_order']);

        return $this->sendResponse($levels, 'Unit levels retrieved.');
    }

    /**
     * Persist a product's unit hierarchy. Accepts an array of
     * { name, factor_to_base } (or a JSON string of it). Always keeps a base
     * "piece" level at factor 1.
     */
    private function syncUnitLevels($productId, $levels): void
    {
        if (is_string($levels)) {
            $levels = json_decode($levels, true);
        }
        if (! is_array($levels)) {
            return;
        }

        \App\Models\ProductUnitLevel::withoutGlobalScope('tenant')
            ->where('product_id', $productId)->delete();

        $sort = 0;
        foreach ($levels as $lvl) {
            $name = trim((string) ($lvl['name'] ?? ''));
            $factor = (float) ($lvl['factor_to_base'] ?? 0);
            if ($name === '' || $factor <= 0) {
                continue;
            }
            \App\Models\ProductUnitLevel::create([
                'product_id'     => $productId,
                'name'           => $name,
                'factor_to_base' => $factor,
                'sort_order'     => $sort++,
            ]);
        }
    }

    /**
     * Persist non-default tier prices for a product. Accepts a map/array of
     * { tier_key: price } (or a JSON string of it).
     */
    private function syncTierPrices($productId, $tierPrices): void
    {
        if (is_string($tierPrices)) {
            $tierPrices = json_decode($tierPrices, true);
        }
        if (! is_array($tierPrices)) {
            return;
        }

        foreach ($tierPrices as $tier => $price) {
            $tier = (string) $tier;
            if ($tier === '' || $tier === 'retail') {
                continue; // retail uses the base product_price
            }
            if ($price === null || $price === '') {
                \App\Models\ProductPrice::withoutGlobalScope('tenant')
                    ->where('product_id', $productId)->where('price_tier', $tier)->delete();
                continue;
            }
            \App\Models\ProductPrice::updateOrCreate(
                ['product_id' => $productId, 'price_tier' => $tier],
                ['price' => (float) $price]
            );
        }
    }

    public function destroy($id): JsonResponse
    {
        $purchaseItemModels = [
            PurchaseItem::class,
        ];
        $saleItemModels = [
            SaleItem::class,
        ];
        $purchaseResult = canDelete($purchaseItemModels, 'product_id', $id);
        $saleResult = canDelete($saleItemModels, 'product_id', $id);

        // Check if product is used in sales
        if ($saleResult) {
            return $this->sendError(__('messages.error.product_used_in_sales'));
        }

        // Check if product is used in purchases
        if ($purchaseResult) {
            return $this->sendError(__('messages.error.product_used_in_purchases'));
        }

        if (File::exists(Storage::path('product_barcode/barcode-PR_' . $id . '.png'))) {
            File::delete(Storage::path('product_barcode/barcode-PR_' . $id . '.png'));
        }

        $product = $this->productRepository->find($id);
        $mainProduct = MainProduct::withCount('products')->find($product->main_product_id);

        if ($mainProduct->product_type == MainProduct::VARIATION_PRODUCT && $mainProduct->products_count <= 1) {
            return $this->sendError(__('messages.error.last_variation_product'));
        }

        VariationProduct::where('product_id', $id)->delete();
        $this->productRepository->delete($id);
        $this->clearProductCache();

        return $this->sendSuccess('Product deleted successfully');
    }

    public function productImageDelete($mediaId): JsonResponse
    {
        $media = Media::where('id', $mediaId)->firstOrFail();
        $media->delete();
        return $this->sendSuccess('Product image deleted successfully');
    }

    public function importProducts(Request $request): JsonResponse
    {
        Excel::import(new ProductImport, request()->file('file'));
        $this->clearProductCache();
        return $this->sendSuccess('Products imported successfully');
    }

    public function getProductExportExcel(Request $request): JsonResponse
    {
        if (Storage::exists('excel/product-excel-export.xlsx')) {
            Storage::delete('excel/product-excel-export.xlsx');
        }
        Excel::store(new ProductExcelExport, 'excel/product-excel-export.xlsx');

        $data['product_excel_url'] = Storage::url('excel/product-excel-export.xlsx');

        return $this->sendResponse($data, 'Product retrieved successfully');
    }

    public function getAllProducts()
    {
        $query = Product::query();
        // Hide over-limit products from sale/POS pickers.
        $cutoff = app(\App\Services\TenantSubscriptionService::class)
            ->productCutoff(\Illuminate\Support\Facades\Auth::user()?->tenant_id);
        if ($cutoff !== null) {
            $query->where('id', '<=', $cutoff);
        }
        $products = $query->get();
        $data = [];

        foreach ($products as $product) {
            $data[] = [
                'id' => $product->id,
                'name' => $product->name,
            ];
        }

        return $this->sendResponse($data, 'Products retrieve successfully.');
    }

    public function generateStandaloneBarcode(Request $request)
    {
        $barcode =  $this->productRepository->generateBarcodeBase64($request->code);
        return $this->sendResponse($barcode, 'Barcode generated successfully.');
    }
}
