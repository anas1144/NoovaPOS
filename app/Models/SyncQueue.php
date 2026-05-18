<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncQueue extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'sync_queue';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SYNCED = 'synced';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'tenant_id',
        'offline_device_id',
        'sync_batch_id',
        'local_uuid',
        'entity_type',
        'operation',
        'payload',
        'status',
        'error_message',
        'server_reference_type',
        'server_reference_id',
        'attempted_at',
        'synced_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempted_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(OfflineDevice::class, 'offline_device_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SyncBatch::class, 'sync_batch_id');
    }
}
