<?php

namespace App\Listeners;

use Stancl\Tenancy\Events\TenancyInitialized;
use Stancl\Tenancy\Listeners\BootstrapTenancy;

/**
 * Bootstraps tenancy (switches DB/cache/etc to the tenant) ONLY for tenants
 * that are flagged to run on their own separate database. Tenants without the
 * flag keep using the central database with row-level tenant_id scoping.
 *
 * The master env flag TENANCY_DB_SWITCH still controls whether this listener is
 * registered at all (see TenancyServiceProvider).
 */
class ConditionalBootstrapTenancy
{
    public function handle(TenancyInitialized $event): void
    {
        $tenant = $event->tenancy->tenant ?? null;

        if ($tenant && method_exists($tenant, 'usesSeparateDb') && $tenant->usesSeparateDb()) {
            app(BootstrapTenancy::class)->handle($event);
        }
    }
}
