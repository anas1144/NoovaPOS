<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * A delivery route (Water Supply / Distribution) assigned to a driver.
 */
class DeliveryRoute extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'delivery_routes';

    protected $fillable = [
        'tenant_id', 'store_id', 'name', 'area', 'driver_user_id', 'status',
    ];

    protected $casts = ['status' => 'boolean'];
}
