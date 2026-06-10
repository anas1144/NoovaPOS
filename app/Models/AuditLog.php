<?php

namespace App\Models;

use App\Traits\CentralConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends BaseModel
{
    use HasFactory, CentralConnection;

    public const UPDATED_TENANT_STATUS = 'tenant.status.updated';
    public const UPDATED_TENANT_SUBSCRIPTION = 'tenant.subscription.updated';
    public const REQUESTED_TENANT_BACKUP = 'tenant.backup.requested';
    public const UPDATED_TENANT_BACKUP = 'tenant.backup.updated';

    public $timestamps = false;

    protected $table = 'audit_logs';

    protected $fillable = [
        'tenant_id',
        'actor_id',
        'event',
        'auditable_type',
        'auditable_id',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withoutGlobalScope('tenant');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(MultiTenant::class, 'tenant_id');
    }
}
