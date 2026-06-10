<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'attendances';

    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LEAVE = 'leave';
    public const STATUS_HALF_DAY = 'half_day';
    public const STATUS_LATE = 'late';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'store_id',
        'shop_id',
        'date',
        'check_in_at',
        'check_out_at',
        'hours_worked',
        'status',
        'note',
        // POS-integrated attendance kiosk fields:
        'check_in_method',
        'check_out_method',
        'work_category',
        'work_seconds',
        'break_seconds',
        'task_seconds',
        'on_break',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'hours_worked' => 'decimal:2',
        'on_break' => 'boolean',
        'work_seconds' => 'integer',
        'break_seconds' => 'integer',
        'task_seconds' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(AttendanceTask::class, 'attendance_id');
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class, 'attendance_id');
    }

    /** The currently running task for this attendance day, if any. */
    public function activeTask()
    {
        return $this->tasks()->where('status', 'running')->latest('id')->first();
    }

    /** The currently open break (started, not ended), if any. */
    public function openBreak()
    {
        return $this->breaks()->whereNull('ended_at')->latest('id')->first();
    }
}
