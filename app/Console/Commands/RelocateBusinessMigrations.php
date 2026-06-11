<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Populates database/migrations/tenant by COPYING every application migration
 * into the tenant set EXCEPT platform-only tables that must live solely in the
 * central database.
 *
 * Why copy (not move):
 *   - Central keeps every table so the super admin (a central user) and the
 *     platform tables keep working.
 *   - Tenant databases get the identity + business tables they need.
 *   - The operation is therefore non-destructive and fully reversible
 *     (just delete database/migrations/tenant/*).
 *
 * Run:
 *   php artisan tenancy:relocate-business-migrations          (dry run)
 *   php artisan tenancy:relocate-business-migrations --copy   (perform copy)
 *
 * After copying:
 *   php artisan tenants:migrate
 *   then set TENANCY_DB_SWITCH=true once verified.
 */
class RelocateBusinessMigrations extends Command
{
    protected $signature = 'tenancy:relocate-business-migrations {--copy : Actually copy the files (otherwise dry-run)}';

    protected $description = 'Copy business + identity migrations into database/migrations/tenant (platform-only tables excluded).';

    /**
     * Filename fragments for migrations that must stay CENTRAL-ONLY.
     * These create platform-level tables that never live in a tenant database.
     */
    private array $centralOnly = [
        'create_tenants_table',
        'create_domains_table',
        'create_plans_table',
        'add_pricing_fields_to_plans_table',
        'create_subscriptions_table',
        'create_audit_logs_table',
        'create_tenant_backups_table',
        // FBR-DI platform-only tables (cross-tenant / super-admin managed).
        'create_agent_tenants_table',
        'create_fbr_di_limits_table',
    ];

    public function handle(): int
    {
        $source = database_path('migrations');
        $target = database_path('migrations' . DIRECTORY_SEPARATOR . 'tenant');

        if (! is_dir($target)) {
            mkdir($target, 0755, true);
        }

        $files = glob($source . DIRECTORY_SEPARATOR . '*.php') ?: [];
        $copy = (bool) $this->option('copy');

        $copied = 0;
        $skippedCentral = 0;
        $skippedExisting = 0;

        foreach ($files as $file) {
            $name = basename($file);

            if ($this->isCentralOnly($name)) {
                $skippedCentral++;
                continue;
            }

            $dest = $target . DIRECTORY_SEPARATOR . $name;
            if (file_exists($dest)) {
                $skippedExisting++;
                continue;
            }

            if ($copy) {
                copy($file, $dest);
            }
            $this->line(($copy ? 'COPIED  ' : 'WOULD COPY  ') . $name);
            $copied++;
        }

        $this->newLine();
        $this->info(($copy ? 'Copied ' : 'Would copy ') . $copied . ' migration(s) into database/migrations/tenant.');
        $this->line("Skipped {$skippedCentral} central-only and {$skippedExisting} already-present file(s).");

        if (! $copy) {
            $this->warn('Dry run only. Re-run with --copy to perform the copy.');
        } else {
            $this->newLine();
            $this->info('Next: php artisan tenants:migrate   (then set TENANCY_DB_SWITCH=true once verified)');
        }

        return self::SUCCESS;
    }

    private function isCentralOnly(string $filename): bool
    {
        foreach ($this->centralOnly as $fragment) {
            if (Str::contains($filename, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
