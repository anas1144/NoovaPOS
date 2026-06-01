<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FbrInvoice extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'fbr_invoices';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SYNCED = 'synced';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETRYING = 'retrying';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'shop_id',
        'fbr_profile_id',
        'sale_id',
        'invoice_no',
        'fbr_invoice_no',
        'fbr_uuid',
        'fbr_qr_payload',
        'mode',
        'status',
        'total_amount',
        'tax_amount',
        'request_payload',
        'response_payload',
        'error_message',
        'attempt_count',
        'submitted_at',
        'synced_at',
        'failed_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'submitted_at' => 'datetime',
        'synced_at' => 'datetime',
        'failed_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(FbrProfile::class, 'fbr_profile_id');
    }
}
