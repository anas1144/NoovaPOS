<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Services\TenantSubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSubscriptionActive
{
    public function __construct(private readonly TenantSubscriptionService $tenantSubscriptionService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->hasRole(Role::SUPER_ADMIN)) {
            return $next($request);
        }

        if (isset($user->status) && !$user->status) {
            abort(403, 'User account is inactive.');
        }

        $this->tenantSubscriptionService->assertTenantCanAccess($user->tenant_id);

        return $next($request);
    }
}
