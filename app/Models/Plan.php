<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends BaseModel
{
    use HasFactory;

    protected $table = 'plans';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'trial_days',
        'max_stores',
        'max_shops',
        'max_registers',
        'max_users',
        'max_products',
        'features',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'status' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
