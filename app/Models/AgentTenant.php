<?php

namespace App\Models;

use App\Traits\CentralConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Agent → Company (tenant) mapping for the FBR Digital Invoice plan type. An
 * agent user manages several companies/tenants. Central (cross-tenant) record.
 */
class AgentTenant extends Model
{
    use CentralConnection;

    protected $table = 'agent_tenants';

    protected $fillable = ['agent_user_id', 'tenant_id'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_user_id');
    }
}
