<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenOrderTicket extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'kitchen_order_tickets';

    public const STATUS_OPEN = 'open';
    public const STATUS_SENT = 'sent';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_READY = 'ready';
    public const STATUS_SERVED = 'served';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'shop_id',
        'kitchen_id',
        'table_id',
        'sale_id',
        'waiter_id',
        'ticket_no',
        'order_type',
        'status',
        'note',
        'sent_to_kitchen_at',
        'ready_at',
        'served_at',
    ];

    protected $casts = [
        'sent_to_kitchen_at' => 'datetime',
        'ready_at' => 'datetime',
        'served_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(KitchenOrderItem::class, 'ticket_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }
}
