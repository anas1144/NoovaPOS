<?php

namespace App\Traits;

/**
 * Pins a model to the CENTRAL database connection.
 *
 * In the hybrid database-per-tenant setup, the DatabaseTenancyBootstrapper
 * switches the DEFAULT connection to the active tenant's database for the
 * duration of a tenant request. Identity and platform tables (users, roles,
 * permissions, Sanctum tokens, tenants, plans, subscriptions, audit logs)
 * must NOT follow that switch — they always live in the central database.
 *
 * Applying this trait forces the model onto the `central` connection
 * regardless of tenancy state, so:
 *   - authentication keeps working on tenant subdomains,
 *   - cross-connection belongsTo/hasMany relations resolve against central,
 *   - the super admin (a central user) can always be loaded.
 *
 * NOTE: the central connection name is read from tenancy config so it stays
 * in sync with `tenancy.database.central_connection`.
 */
trait CentralConnection
{
    public function getConnectionName()
    {
        return config('tenancy.database.central_connection', 'central');
    }
}
