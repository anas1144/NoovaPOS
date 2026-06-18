<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line item of a distribution van load (loaded / delivered / returned qty).
 */
class DispatchLoadItem extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'dispatch_load_items';

    protected $fillable = [
        'tenant_id', 'dispatch_load_id', 'product_id',
        'loaded_qty', 'delivered_qty', 'returned_qty',
    ];

    protected $casts = [
        'loaded_qty'    => 'decimal:2',
        'delivered_qty' => 'decimal:2',
        'returned_qty'  => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
