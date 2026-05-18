<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FbrProfile extends BaseModel
{
    use Multitenantable;

    protected $table = 'fbr_profiles';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'business_name',
        'ntn',
        'strn',
        'province',
        'business_activity',
        'pos_id',
        'sandbox_token',
        'production_token',
        'mode',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
}

