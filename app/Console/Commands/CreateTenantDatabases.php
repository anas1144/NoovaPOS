<?php

namespace App\Console\Commands;

use App\Models\MultiTenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Ensures every existing tenant has its own physical database and runs the
 * tenant migration set into it.
 *
 * The TenantCreated job pipeline only provisions a database for tenants created
 * AFTER it was enabled. Tenants that already existed (e.g. the demo tenant)
 * never got a database — this command backfills them.
 *
 *   php artisan tenancy:create-databases            # create missing DBs + migrate
 *   php artisan tenancy:create-databases --fresh    # drop & recreate (DESTRUCTIVE)
 */
class CreateTenantDatabases extends Command
{
    protected $signature = 'tenancy:create-databases {--fresh : Drop and recreate each tenant database (destructive)}';

    protected $description = 'Create (and migrate) a database for every existing tenant.';

    public function handle(): int
    {
        $tenants = MultiTenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $name = $tenant->database()->getName();
            $manager = $tenant->database()->manager();
            $exists = ! empty(DB::select(
                'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
                [$name]
            ));

            if ($this->option('fresh') && $exists) {
                $manager->deleteDatabase($tenant);
                $exists = false;
                $this->warn("Dropped {$name}");
            }

            if (! $exists) {
                $manager->createDatabase($tenant);
                $this->info("Created database: {$name}");
            } else {
                $this->line("Already exists: {$name}");
            }
        }

        $this->newLine();
        $this->info('Running tenant migrations into each tenant database…');
        Artisan::call('tenants:migrate', ['--force' => true], $this->getOutput());

        $this->newLine();
        $this->info('Done. Check phpMyAdmin — you should now see the noovapos_tenant_* databases.');

        return self::SUCCESS;
    }
}
