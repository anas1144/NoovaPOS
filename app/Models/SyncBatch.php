<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyncBatch extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'sync_batches';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SYNCED = 'synced';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PARTIAL = 'partial';

    protected $fillable = [
        'tenant_id',
        'offline_device_id',
        'batch_uuid',
        'status',
        'items_count',
        'synced_count',
        'failed_count',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(OfflineDevice::class, 'offline_device_id');
    }

    public function queueItems(): HasMany
    {
        return $this->hasMany(SyncQueue::class);
    }
}
