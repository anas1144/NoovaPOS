<?php

namespace App\Console\Commands;

use App\Models\MultiTenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Ensures every tenant that is flagged to run on its OWN database
 * (uses_separate_db = true) has a physical database, and runs the tenant
 * migration set into it.
 *
 * Tenants WITHOUT the flag intentionally stay on the central database, so they
 * are skipped here — they don't need (and shouldn't get) a separate database.
 *
 *   php artisan tenancy:create-databases            # create missing DBs + migrate (flagged tenants)
 *   php artisan tenancy:create-databases --fresh    # drop & recreate (DESTRUCTIVE)
 *   php artisan tenancy:create-databases --all       # include tenants not flagged (rarely needed)
 */
class CreateTenantDatabases extends Command
{
    protected $signature = 'tenancy:create-databases
        {--fresh : Drop and recreate each tenant database (destructive)}
        {--all : Provision every tenant, not just those flagged uses_separate_db}';

    protected $description = 'Create (and migrate) a database for each separate-DB tenant.';

    public function handle(): int
    {
        $all = MultiTenant::all();

        if ($all->isEmpty()) {
            $this->warn('No tenants found.');
            return self::SUCCESS;
        }

        // Diagnostics: show every tenant and whether it uses a separate DB.
        $this->line('Tenants and their Separate-DB flag:');
        foreach ($all as $t) {
            $flag = $t->uses_separate_db ? '<info>separate-db</info>' : 'central';
            $this->line("  - {$t->id}  [{$flag}]");
        }
        $this->newLine();

        $tenants = $this->option('all')
            ? $all
            : $all->filter(fn ($t) => (bool) $t->uses_separate_db)->values();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants have Separate DB enabled (uses_separate_db = true).');
            $this->line('Enable it on Platform → Tenants for the tenant you want isolated, then re-run.');
            $this->line('(Or pass --all to provision every tenant regardless of the flag.)');
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
        $this->info('Running tenant migrations into each separate-DB tenant…');

        foreach ($tenants as $tenant) {
            $this->line("Tenant: {$tenant->id}");
            self::migrateTenantDatabase($tenant, $this->getOutput());
        }

        $this->newLine();
        $this->info('Done. Check phpMyAdmin — the noovapos_tenant_* databases should now have tables.');

        return self::SUCCESS;
    }

    /**
     * Migrate the tenant migration set directly into the tenant's own database.
     *
     * We do NOT rely on stancl's runtime connection-switch here: we register a
     * throwaway connection pointed straight at the tenant database and run the
     * standard migrate command against it. This guarantees the migrations land
     * in the tenant DB regardless of bootstrapper/listener timing.
     */
    public static function migrateTenantDatabase(MultiTenant $tenant, $output = null): void
    {
        $centralName = config('tenancy.database.central_connection') ?: config('database.default');
        $base = config("database.connections.{$centralName}");
        $connection = 'tenant_provision';

        config(["database.connections.{$connection}" => array_merge($base, [
            'database' => $tenant->database()->getName(),
        ])]);

        DB::purge($connection);

        $params = [
            '--database' => $connection,
            '--path'     => 'database/migrations/tenant',
            '--force'    => true,
        ];

        if ($output) {
            Artisan::call('migrate', $params, $output);
        } else {
            Artisan::call('migrate', $params);
        }

        DB::purge($connection);
    }
}
