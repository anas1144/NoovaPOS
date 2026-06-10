<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An employee's biometric enrolment (face descriptors / future fingerprint).
 * `descriptors` is JSON: for faces, an array of face-api.js 128-float vectors.
 */
class EmployeeBiometric extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'employee_biometrics';

    public const TYPE_FACE        = 'face';
    public const TYPE_FINGERPRINT = 'fingerprint';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'type',
        'descriptors',
        'samples_count',
        'status',
        'last_registered_at',
    ];

    protected $casts = [
        'descriptors'        => 'array',
        'samples_count'      => 'integer',
        'last_registered_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
