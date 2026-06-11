<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * An FBR DI sandbox test scenario (SN001…SN028) and its last run result. The
 * scenario catalogue is global reference data — intentionally NOT tenant-scoped.
 */
class FbrSandboxScenario extends BaseModel
{
    use HasFactory;

    protected $table = 'fbr_sandbox_scenarios';

    protected $fillable = [
        'tenant_id', 'code', 'title', 'description', 'payload',
        'status', 'last_result', 'response', 'last_run_at',
    ];

    protected $casts = [
        'payload'     => 'array',
        'last_run_at' => 'datetime',
    ];
}
