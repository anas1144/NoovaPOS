<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * A customer bottle deposit (held money, refundable on bottle return).
 */
class WaterDeposit extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'water_deposits';

    protected $fillable = [
        'tenant_id', 'customer_id', 'amount', 'bottles', 'status',
        'date', 'refunded_at', 'note',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'bottles'     => 'decimal:2',
        'date'        => 'date',
        'refunded_at' => 'datetime',
    ];
}
