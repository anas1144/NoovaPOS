<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A product's price for a non-default tier.
 */
class ProductPrice extends Model
{
    use Multitenantable;

    protected $table = 'product_prices';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'price_tier',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
