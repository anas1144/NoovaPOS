<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationOutbox extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'notifications_outbox';

    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_PUSH = 'push';
    public const CHANNEL_IN_APP = 'in_app';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_READ = 'read';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'customer_id',
        'channel',
        'event',
        'subject',
        'body',
        'recipient',
        'status',
        'payload',
        'sent_at',
        'read_at',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];
}
