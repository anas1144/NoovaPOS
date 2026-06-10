<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

/**
 * Tenant-aware API/web middleware.
 *
 * ┌──────────────────────────────────────────────────────────────────┐
 * │  Central domains (noovapos.local, superadmin.noovapos.local)     │
 * │  → Skip tenancy, pass request straight through.                  │
 * │                                                                  │
 * │  Tenant domains  (abcgroup.noovapos.local, …)                    │
 * │  → Initialize tenancy by FULL hostname (domain lookup).          │
 * └──────────────────────────────────────────────────────────────────┘
 *
 * Why InitializeTenancyByDomain instead of BySubdomain:
 *
 * Our domains table stores FULL hostnames (e.g. "abcgroup.noovapos.local").
 * InitializeTenancyBySubdomain strips the prefix ("abcgroup") and looks that
 * up — it finds nothing because the row stores the full domain.
 * InitializeTenancyByDomain passes $request->getHost() unchanged, which
 * matches the stored value exactly.
 */
class InitializeTenancyForApi
{
    public function handle(Request $request, Closure $next): mixed
    {
        $host = $request->getHost();

        // Central domains → no tenancy needed, pass straight through
        if ($this->isCentralDomain($host)) {
            return $next($request);
        }

        // Tenant domain → initialize tenancy using the full hostname
        try {
            return app(InitializeTenancyByDomain::class)->handle($request, $next);
        } catch (\Throwable $e) {
            // Unknown domain — return 404 rather than a 500
            abort(404, 'Tenant not found for this domain.');
        }
    }

    private function isCentralDomain(string $host): bool
    {
        // Exact match against configured central domains
        return in_array($host, config('tenancy.central_domains', []), true);
    }
}
