<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-tenant (optionally per-store) attendance configuration JSON blob.
 * Read/written through AttendanceSettingService, which layers defaults.
 */
class AttendanceSetting extends Model
{
    use Multitenantable;

    protected $table = 'attendance_settings';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];
}
