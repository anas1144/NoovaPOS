<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An FBR Digital Invoice seller "Business" (maps to a Store). A Company (tenant)
 * may own several.
 */
class FbrBusiness extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'fbr_businesses';

    protected $fillable = [
        'tenant_id', 'store_id', 'name', 'ntn_cnic', 'strn', 'province',
        'address', 'business_activity', 'pos_id', 'environment',
        'sandbox_token', 'production_token', 'status',
    ];

    protected $casts = ['status' => 'boolean'];

    protected $hidden = ['sandbox_token', 'production_token'];

    public function invoices(): HasMany
    {
        return $this->hasMany(FbrDiInvoice::class, 'fbr_business_id');
    }
}
