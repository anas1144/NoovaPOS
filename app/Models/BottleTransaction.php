<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * A bottle issue/return ledger entry for a customer (Water Supply).
 */
class BottleTransaction extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'bottle_transactions';

    public const TYPE_ISSUE  = 'issue';
    public const TYPE_RETURN = 'return';

    protected $fillable = [
        'tenant_id', 'customer_id', 'delivery_route_id', 'type',
        'quantity', 'date', 'note', 'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'date'     => 'date',
    ];
}
