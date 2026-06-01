<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmLead extends BaseModel
{
    use HasFactory, Multitenantable;

    protected $table = 'crm_leads';

    public const STAGE_NEW = 'new';
    public const STAGE_CONTACTED = 'contacted';
    public const STAGE_QUALIFIED = 'qualified';
    public const STAGE_PROPOSAL = 'proposal';
    public const STAGE_WON = 'won';
    public const STAGE_LOST = 'lost';

    public const PIPELINE_STAGES = [
        self::STAGE_NEW,
        self::STAGE_CONTACTED,
        self::STAGE_QUALIFIED,
        self::STAGE_PROPOSAL,
        self::STAGE_WON,
        self::STAGE_LOST,
    ];

    protected $fillable = [
        'tenant_id',
        'assigned_to',
        'customer_id',
        'name',
        'email',
        'phone',
        'company',
        'source',
        'stage',
        'estimated_value',
        'probability',
        'expected_close_date',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'probability' => 'integer',
        'expected_close_date' => 'date',
        'closed_at' => 'date',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
