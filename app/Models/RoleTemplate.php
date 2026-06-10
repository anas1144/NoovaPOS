<?php

namespace App\Models;

use App\Traits\CentralConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Super-admin-managed role+permission template, scoped to a shop type.
 * Lives in the central database (platform-level configuration).
 */
class RoleTemplate extends Model
{
    use HasFactory, CentralConnection;

    protected $table = 'role_templates';

    protected $fillable = [
        'name',
        'display_name',
        'shop_type',
        'permissions',
        'is_system',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system'   => 'boolean',
    ];
}
