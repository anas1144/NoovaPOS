<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantTable extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'restaurant_tables';

    public const STATE_FREE = 'free';
    public const STATE_OCCUPIED = 'occupied';
    public const STATE_RESERVED = 'reserved';
    public const STATE_BILLED = 'billed';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'shop_id',
        'hall_id',
        'name',
        'code',
        'seats',
        'state',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'seats' => 'integer',
    ];

    public function hall(): BelongsTo
    {
        return $this->belongsTo(RestaurantHall::class, 'hall_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
