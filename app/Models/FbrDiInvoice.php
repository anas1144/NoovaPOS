<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An FBR Digital Invoice (standalone FBR-DI module — distinct from the POS-side
 * FbrInvoice add-on).
 */
class FbrDiInvoice extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'fbr_di_invoices';

    public const STATUS_DRAFT        = 'draft';
    public const STATUS_PENDING_SYNC = 'pending_sync';
    public const STATUS_SYNCING      = 'syncing';
    public const STATUS_SYNCED       = 'synced';
    public const STATUS_ACCEPTED     = 'accepted';
    public const STATUS_REJECTED     = 'rejected';
    public const STATUS_CANCELLED    = 'cancelled';

    /** Statuses that lock the invoice from deletion/editing. */
    public const LOCKED = ['synced', 'accepted', 'rejected'];

    protected $fillable = [
        'tenant_id', 'fbr_business_id', 'local_no', 'fbr_invoice_no', 'ref_inv_no',
        'invoice_type', 'invoice_date',
        'buyer_ntn_cnic', 'buyer_name', 'buyer_registration_type', 'buyer_province', 'buyer_address',
        'value_excl_tax', 'sales_tax', 'further_tax', 'total_incl_tax',
        'status', 'qr_payload', 'sync_response', 'sync_error', 'synced_at', 'created_by',
    ];

    protected $casts = [
        'invoice_date'   => 'date',
        'value_excl_tax' => 'decimal:2',
        'sales_tax'      => 'decimal:2',
        'further_tax'    => 'decimal:2',
        'total_incl_tax' => 'decimal:2',
        'synced_at'      => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(FbrBusiness::class, 'fbr_business_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FbrDiInvoiceItem::class, 'fbr_di_invoice_id');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, self::LOCKED, true);
    }
}
