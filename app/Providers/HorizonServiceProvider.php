<?php

namespace App\Providers;

use App\Models\Role;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

/**
 * Horizon queue dashboard + auth gate.
 *
 * The dashboard (/horizon) is restricted to the platform super admin — it
 * exposes every tenant's queued jobs (FBR sync, imports, notifications), so it
 * must never be reachable by tenant users.
 */
class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // Optional: a subtle theme tweak for the dashboard.
        // Horizon::night();
    }

    /**
     * Only the platform super admin may open the Horizon dashboard.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            if (! $user) {
                return false;
            }

            // Spatie role check, with a hard-coded super-admin email fallback so
            // the dashboard is reachable even before roles are seeded.
            if (method_exists($user, 'hasRole') && $user->hasRole(Role::SUPER_ADMIN)) {
                return true;
            }

            return in_array($user->email, [
                'superadmin@noovapos-gls.com',
            ], true);
        });
    }
}
