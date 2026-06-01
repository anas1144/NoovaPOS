<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerSubscription extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'customer_subscriptions';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_ENDED = 'ended';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'recurring_plan_id',
        'store_id',
        'shop_id',
        'default_quantity',
        'schedule_days',
        'route_name',
        'delivery_address',
        'start_date',
        'next_invoice_date',
        'status',
        'deposit_paid',
        'bottles_with_customer',
        'notes',
    ];

    protected $casts = [
        'schedule_days' => 'array',
        'start_date' => 'date',
        'next_invoice_date' => 'date',
        'default_quantity' => 'decimal:3',
        'deposit_paid' => 'decimal:2',
        'bottles_with_customer' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(RecurringPlan::class, 'recurring_plan_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(DeliverySchedule::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(RecurringInvoice::class);
    }
}
