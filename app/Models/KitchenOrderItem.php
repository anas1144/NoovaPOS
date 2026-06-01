<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenOrderItem extends BaseModel
{
    use HasFactory;

    protected $table = 'kitchen_order_items';

    protected $fillable = [
        'ticket_id',
        'product_id',
        'product_name',
        'quantity',
        'modifier',
        'status',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(KitchenOrderTicket::class, 'ticket_id');
    }
}
