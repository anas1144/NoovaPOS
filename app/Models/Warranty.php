<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A warranty registration for a serial-numbered product (Electronics).
 */
class Warranty extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'warranties';

    protected $fillable = [
        'tenant_id', 'product_id', 'serial_no', 'customer_id', 'sale_id',
        'start_date', 'months', 'end_date', 'status', 'note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'months'     => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
