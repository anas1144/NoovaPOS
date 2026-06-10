<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A registered WebAuthn platform-authenticator credential for an employee.
 */
class WebauthnCredential extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'webauthn_credentials';

    protected $fillable = [
        'tenant_id', 'employee_id', 'credential_id', 'public_key',
        'algorithm', 'sign_count', 'label', 'last_used_at',
    ];

    protected $casts = [
        'algorithm'    => 'integer',
        'sign_count'   => 'integer',
        'last_used_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
