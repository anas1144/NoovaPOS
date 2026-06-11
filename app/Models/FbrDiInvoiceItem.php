<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line item of an FBR Digital Invoice.
 */
class FbrDiInvoiceItem extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'fbr_di_invoice_items';

    protected $fillable = [
        'tenant_id', 'fbr_di_invoice_id', 'product_id', 'sr_no', 'hs_code',
        'description', 'uom', 'rate_per_unit', 'quantity', 'value_excl_tax',
        'rate_of_sales_tax', 'value_of_sales_tax', 'value_incl_tax',
    ];

    protected $casts = [
        'rate_per_unit'      => 'decimal:2',
        'quantity'           => 'decimal:2',
        'value_excl_tax'     => 'decimal:2',
        'rate_of_sales_tax'  => 'decimal:2',
        'value_of_sales_tax' => 'decimal:2',
        'value_incl_tax'     => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FbrDiInvoice::class, 'fbr_di_invoice_id');
    }
}
