<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A raw ingredient line of a recipe (quantity per single yield unit).
 */
class RecipeItem extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'recipe_items';

    protected $fillable = ['tenant_id', 'recipe_id', 'product_id', 'quantity'];

    protected $casts = ['quantity' => 'decimal:4'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
