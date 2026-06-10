<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A task worked on during an attendance day (office / personal), with
 * start / pause / resume / complete and accumulated duration.
 */
class AttendanceTask extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'attendance_tasks';

    public const CATEGORY_OFFICE   = 'office';
    public const CATEGORY_PERSONAL = 'personal';

    public const STATUS_RUNNING   = 'running';
    public const STATUS_PAUSED    = 'paused';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'tenant_id',
        'attendance_id',
        'employee_id',
        'name',
        'category',
        'status',
        'started_at',
        'resumed_at',
        'ended_at',
        'duration_seconds',
        'notes',
    ];

    protected $casts = [
        'started_at'       => 'datetime',
        'resumed_at'       => 'datetime',
        'ended_at'         => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
