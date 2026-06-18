<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A serial-numbered unit of a product (Electronics).
 */
class ProductSerial extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'product_serials';

    protected $fillable = [
        'tenant_id', 'product_id', 'store_id', 'serial_no',
        'status', 'sale_id', 'sold_at', 'note',
    ];

    protected $casts = ['sold_at' => 'datetime'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
