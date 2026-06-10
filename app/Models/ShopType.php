<?php

namespace App\Models;

use App\Traits\CentralConnection;
use Illuminate\Database\Eloquent\Model;

/**
 * Super-admin-managed registry of selectable shop/business types (central).
 */
class ShopType extends Model
{
    use CentralConnection;

    protected $table = 'shop_types';

    protected $fillable = ['key', 'label', 'enabled', 'sort_order'];

    protected $casts = [
        'enabled'    => 'boolean',
        'sort_order' => 'integer',
    ];
}
