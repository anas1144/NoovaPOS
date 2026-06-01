<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'employees';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'store_id',
        'shop_id',
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'cnic',
        'designation',
        'department',
        'employment_type',
        'hired_at',
        'terminated_at',
        'salary',
        'salary_cycle',
        'address',
        'status',
    ];

    protected $casts = [
        'hired_at' => 'date',
        'terminated_at' => 'date',
        'salary' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . ($this->last_name ?? ''));
    }
}
