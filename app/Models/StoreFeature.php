<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-store feature toggle.
 */
class StoreFeature extends Model
{
    use Multitenantable;

    protected $table = 'store_features';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'feature_key',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
