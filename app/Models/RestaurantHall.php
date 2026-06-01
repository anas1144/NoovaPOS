<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantHall extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'restaurant_halls';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'shop_id',
        'name',
        'code',
        'floor',
        'capacity',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'floor' => 'integer',
        'capacity' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class, 'hall_id');
    }
}
