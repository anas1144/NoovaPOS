<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Model;

/**
 * A pricing tier (retail, wholesale, m20, …) for the tenant.
 */
class PriceTier extends Model
{
    use Multitenantable;

    protected $table = 'price_tiers';

    protected $fillable = [
        'tenant_id',
        'key',
        'label',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];
}
