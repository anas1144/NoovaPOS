<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Model;

/**
 * An order placed by a customer on a self-service display.
 */
class CustomerOrder extends Model
{
    use Multitenantable;

    public const STATUS_NEW       = 'new';
    public const STATUS_ACCEPTED  = 'accepted';
    public const STATUS_ASSIGNED  = 'assigned';
    public const STATUS_SERVED    = 'served';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'customer_orders';

    protected $fillable = [
        'tenant_id', 'store_id', 'shop_id', 'display_id', 'kitchen_id', 'table_id',
        'kot_id', 'waiter_id', 'token_no', 'customer_name', 'seats', 'items',
        'total', 'status',
    ];

    protected $casts = [
        'seats' => 'array',
        'items' => 'array',
        'total' => 'decimal:2',
    ];
}
