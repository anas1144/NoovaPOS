<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringPlan extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'recurring_plans';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'name',
        'billing_cycle',
        'price',
        'deposit_amount',
        'is_bottle',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'is_bottle' => 'boolean',
        'status' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CustomerSubscription::class);
    }
}
