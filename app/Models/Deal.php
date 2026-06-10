<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A deal / combo — a bundle of products sold at a fixed price.
 */
class Deal extends Model
{
    use Multitenantable;

    protected $table = 'deals';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'name',
        'code',
        'price',
        'status',
    ];

    protected $casts = [
        'price'  => 'decimal:2',
        'status' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DealItem::class, 'deal_id');
    }
}
