<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A distribution van load dispatched to a route (Distribution).
 */
class DispatchLoad extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'dispatch_loads';

    protected $fillable = [
        'tenant_id', 'delivery_route_id', 'driver_user_id', 'date',
        'vehicle', 'status', 'note', 'created_by',
    ];

    protected $casts = ['date' => 'date'];

    public function items(): HasMany
    {
        return $this->hasMany(DispatchLoadItem::class, 'dispatch_load_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class, 'delivery_route_id');
    }
}
