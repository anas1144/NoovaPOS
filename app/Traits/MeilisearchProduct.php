<?php

namespace App\Traits;

/**
 * Plug-and-play Meilisearch indexing for the Product model.
 *
 * - When `laravel/scout` is installed and configured (SCOUT_DRIVER=meilisearch)
 *   Eloquent will pick up `toSearchableArray()` and call it.
 * - When Scout isn't present, this trait is harmless — it just adds a method
 *   on Product that's never invoked, so the app keeps booting.
 *
 * To enable:
 *   composer require laravel/scout meilisearch/meilisearch-php
 *   php artisan vendor:publish --provider="Laravel\\Scout\\ScoutServiceProvider"
 *   php artisan scout:import "App\\Models\\Product"
 *
 * Then `use Laravel\Scout\Searchable;` on the Product model and Scout will
 * call `toSearchableArray()` defined here.
 */
trait MeilisearchProduct
{
    /**
     * Index name (Meilisearch).
     */
    public function searchableAs(): string
    {
        return 'products';
    }

    /**
     * Per-record document.
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['productCategory:id,name', 'brand:id,name']);

        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'code' => $this->code,
            'product_code' => $this->product_code,
            'barcode' => $this->barcode_symbology ?? null,
            'category' => $this->productCategory?->name,
            'brand' => $this->brand?->name,
            'product_price' => (float) ($this->product_price ?? 0),
            'product_cost' => (float) ($this->product_cost ?? 0),
            'stock_quantity' => (float) ($this->stock_quantity ?? 0),
        ];
    }

    /**
     * Filter on tenant when querying.
     */
    public function makeAllSearchableUsing($query)
    {
        return $query->with(['productCategory:id,name', 'brand:id,name']);
    }
}
