<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockMovement extends BaseModel
{
    use HasFactory;

    protected $table = 'stock_movements';

    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'shop_id',
        'warehouse_id',
        'product_id',
        'movement_type',
        'direction',
        'quantity',
        'reference_type',
        'reference_id',
        'created_by',
    ];
}
