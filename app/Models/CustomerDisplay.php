<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer self-service display (kiosk), mounted at a store/shop and attached
 * to a kitchen. Reached publicly via its token.
 */
class CustomerDisplay extends Model
{
    use Multitenantable;

    protected $table = 'customer_displays';

    protected $fillable = [
        'tenant_id', 'store_id', 'shop_id', 'kitchen_id', 'name', 'token', 'status',
    ];

    protected $casts = ['status' => 'boolean'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function kitchen(): BelongsTo
    {
        return $this->belongsTo(Kitchen::class, 'kitchen_id');
    }
}
