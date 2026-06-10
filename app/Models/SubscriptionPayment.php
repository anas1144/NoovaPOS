<?php

namespace App\Models;

use App\Traits\CentralConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A SaaS subscription payment / renewal request (central).
 */
class SubscriptionPayment extends Model
{
    use HasFactory, CentralConnection;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_PAID     = 'paid';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'subscription_payments';

    protected $fillable = [
        'tenant_id',
        'type',
        'plan_id',
        'billing_cycle',
        'periods',
        'addon_shops',
        'addon_users',
        'addon_products',
        'amount',
        'currency',
        'status',
        'method',
        'pay_method',
        'checkout_channel',
        'checkout_reference',
        'checkout_token',
        'reference',
        'proof_path',
        'note',
        'period_start',
        'period_end',
        'paid_at',
        'requested_by',
        'confirmed_by',
    ];

    protected $casts = [
        'periods'      => 'integer',
        'amount'       => 'decimal:2',
        'period_start' => 'datetime',
        'period_end'   => 'datetime',
        'paid_at'      => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
