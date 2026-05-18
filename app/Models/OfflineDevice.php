<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfflineDevice extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'offline_devices';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'shop_id',
        'device_uuid',
        'name',
        'platform',
        'app_version',
        'status',
        'last_seen_at',
    ];

    protected $casts = [
        'status' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(SyncBatch::class);
    }

    public function queueItems(): HasMany
    {
        return $this->hasMany(SyncQueue::class);
    }
}
