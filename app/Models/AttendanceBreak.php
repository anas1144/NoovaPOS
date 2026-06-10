<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A break period within an attendance day (start break → end break).
 */
class AttendanceBreak extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'attendance_breaks';

    protected $fillable = [
        'tenant_id',
        'attendance_id',
        'employee_id',
        'started_at',
        'ended_at',
        'duration_seconds',
    ];

    protected $casts = [
        'started_at'       => 'datetime',
        'ended_at'         => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }
}
