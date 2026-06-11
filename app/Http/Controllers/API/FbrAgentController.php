<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\AgentTenant;
use App\Models\MultiTenant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Super-admin management of FBR Agents and the Companies (tenants) they manage.
 */
class FbrAgentController extends AppBaseController
{
    /** Agents (users with the fbr_agent role) + the companies assigned to each. */
    public function index(Request $request): JsonResponse
    {
        $tenantNames = MultiTenant::query()->get()->mapWithKeys(fn ($t) => [$t->id => ($t->name ?? $t->id)]);
        $map = AgentTenant::query()->get()->groupBy('agent_user_id');

        $agents = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', Role::FBR_AGENT))
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->map(function ($u) use ($map, $tenantNames) {
                $companies = ($map->get($u->id) ?? collect())->map(fn ($r) => [
                    'tenant_id' => $r->tenant_id,
                    'company'   => $tenantNames[$r->tenant_id] ?? $r->tenant_id,
                ])->values();
                return [
                    'id'        => $u->id,
                    'name'      => trim("{$u->first_name} {$u->last_name}"),
                    'email'     => $u->email,
                    'companies' => $companies,
                ];
            });

        return $this->sendResponse([
            'agents'    => $agents,
            'companies' => $tenantNames->map(fn ($name, $id) => ['tenant_id' => $id, 'company' => $name])->values(),
        ], 'Agents retrieved.');
    }

    public function attach(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_user_id' => 'required|integer',
            'tenant_id'     => 'required|string',
        ]);

        AgentTenant::firstOrCreate($data);

        return $this->sendSuccess('Company assigned to agent.');
    }

    public function detach(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_user_id' => 'required|integer',
            'tenant_id'     => 'required|string',
        ]);

        AgentTenant::query()
            ->where('agent_user_id', $data['agent_user_id'])
            ->where('tenant_id', $data['tenant_id'])
            ->delete();

        return $this->sendSuccess('Company removed from agent.');
    }
}
