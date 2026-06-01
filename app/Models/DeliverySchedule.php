<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliverySchedule extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'delivery_schedules';

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'customer_subscription_id',
        'customer_id',
        'scheduled_date',
        'quantity',
        'extra_quantity',
        'driver_id',
        'route_name',
        'status',
        'delivered_at',
        'bottles_returned',
        'note',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'delivered_at' => 'datetime',
        'quantity' => 'decimal:3',
        'extra_quantity' => 'decimal:3',
        'bottles_returned' => 'integer',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CustomerSubscription::class, 'customer_subscription_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
