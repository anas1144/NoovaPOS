<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A push-notification token (FCM/APNs) for a user's device.
 */
class DeviceToken extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'device_tokens';

    protected $fillable = [
        'tenant_id', 'user_id', 'token', 'platform', 'device_name', 'last_seen_at',
    ];

    protected $casts = ['last_seen_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
