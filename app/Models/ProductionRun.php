<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A production run of a recipe (finished output + consumed ingredients snapshot).
 */
class ProductionRun extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'production_runs';

    protected $fillable = [
        'tenant_id', 'recipe_id', 'date', 'batches', 'produced_qty',
        'wastage_qty', 'consumed', 'status', 'note', 'created_by',
    ];

    protected $casts = [
        'date'         => 'date',
        'batches'      => 'decimal:2',
        'produced_qty' => 'decimal:2',
        'wastage_qty'  => 'decimal:2',
        'consumed'     => 'array',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class, 'recipe_id');
    }
}
