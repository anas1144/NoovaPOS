<?php

namespace App\Models;

use App\Traits\CentralConnection;
use Illuminate\Database\Eloquent\Model;

/**
 * Cumulative per-tenant add-on allowance (central).
 */
class TenantAddon extends Model
{
    use CentralConnection;

    protected $table = 'tenant_addons';

    protected $fillable = [
        'tenant_id',
        'extra_shops',
        'extra_users',
        'extra_products',
        'fbr_enabled',
        'fbr_expires_at',
    ];

    protected $casts = [
        'extra_shops'    => 'integer',
        'extra_users'    => 'integer',
        'extra_products' => 'integer',
        'fbr_enabled'    => 'boolean',
        'fbr_expires_at' => 'datetime',
    ];
}
