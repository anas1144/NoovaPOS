<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A product/item in the FBR Digital Invoicing catalog (separate from POS
 * products). Used to auto-fill invoice line items. Scoped per tenant.
 */
class FbrDiProduct extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'fbr_di_products';

    protected $fillable = [
        'tenant_id', 'fbr_business_id', 'name', 'hs_code', 'uom',
        'rate_per_unit', 'rate_of_sales_tax', 'sro_no', 'sro_item_serial',
        'sale_type', 'category', 'status',
    ];

    protected $casts = [
        'rate_per_unit'     => 'decimal:2',
        'rate_of_sales_tax' => 'decimal:2',
        'status'            => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(FbrBusiness::class, 'fbr_business_id');
    }
}
