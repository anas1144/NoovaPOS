<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A production recipe (finished product + raw ingredients).
 */
class Recipe extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'recipes';

    protected $fillable = ['tenant_id', 'product_id', 'name', 'yield_qty', 'status'];

    protected $casts = ['yield_qty' => 'decimal:2', 'status' => 'boolean'];

    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class, 'recipe_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
