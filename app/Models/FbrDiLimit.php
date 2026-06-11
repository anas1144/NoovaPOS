<?php

namespace App\Models;

use App\Traits\CentralConnection;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-Company FBR-DI plan limits / overrides (central — managed by super admin).
 */
class FbrDiLimit extends Model
{
    use CentralConnection;

    protected $table = 'fbr_di_limits';

    public const PLAN_SINGLE = 'single';
    public const PLAN_AGENT  = 'agent';

    protected $fillable = [
        'tenant_id', 'plan_type', 'monthly_invoice_limit', 'businesses_limit',
    ];

    protected $casts = [
        'monthly_invoice_limit' => 'integer',
        'businesses_limit'      => 'integer',
    ];
}
