<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An employee-submitted attendance request awaiting manager review.
 */
class AttendanceRequest extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'attendance_requests';

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'tenant_id', 'employee_id', 'type', 'date',
        'requested_check_in', 'requested_check_out', 'reason',
        'status', 'reviewed_by', 'review_note',
    ];

    protected $casts = [
        'date'                => 'date',
        'requested_check_in'  => 'datetime',
        'requested_check_out' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
