<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A kitchen within a restaurant-type store. KOTs route to the kitchen assigned
 * to the waiter who sends them.
 */
class Kitchen extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'kitchens';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
