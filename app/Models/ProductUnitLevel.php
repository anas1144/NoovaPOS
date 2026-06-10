<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A unit level in a product's unit hierarchy (factor_to_base = base units per 1).
 */
class ProductUnitLevel extends Model
{
    use Multitenantable;

    protected $table = 'product_unit_levels';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'name',
        'factor_to_base',
        'sort_order',
    ];

    protected $casts = [
        'factor_to_base' => 'decimal:4',
        'sort_order'     => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
