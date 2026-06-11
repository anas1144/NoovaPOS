<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * FBR error-code reference + occurrence tracking (Error Center). FBR error codes
 * are global reference data, so this model is intentionally NOT tenant-scoped.
 */
class FbrErrorCode extends BaseModel
{
    use HasFactory;

    protected $table = 'fbr_error_codes';

    protected $fillable = [
        'tenant_id', 'code', 'message', 'description', 'resolution',
        'last_occurrence_at', 'total_occurrences',
    ];

    protected $casts = [
        'last_occurrence_at' => 'datetime',
        'total_occurrences'  => 'integer',
    ];
}
