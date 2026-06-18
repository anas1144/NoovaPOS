<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A batch of a product with its own expiry date and on-hand quantity.
 */
class ProductBatch extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'product_batches';

    protected $fillable = [
        'tenant_id', 'product_id', 'store_id', 'batch_no',
        'mfg_date', 'expiry_date', 'quantity', 'cost', 'status',
    ];

    protected $casts = [
        'mfg_date'    => 'date',
        'expiry_date' => 'date',
        'quantity'    => 'decimal:2',
        'cost'        => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
