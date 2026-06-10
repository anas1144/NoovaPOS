<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Used by: php artisan tenants:seed
 *
 * Seeds the TENANT-scoped data only.
 * Permissions, roles, and plans live in the central DB — do NOT re-seed them here.
 * SettingTableSeeder creates: currencies, default warehouse, walk-in customer, settings.
 *
 * NOTE: during the incremental cut-over to per-tenant databases, the business
 * tables this seeder depends on may not yet exist in the tenant DB. The guard
 * below makes the seeder a safe no-op until those migrations are moved into
 * database/migrations/tenant, so `tenants:seed` never crashes a fresh tenant DB.
 */
class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('settings')) {
            $this->command?->warn(
                'TenantDatabaseSeeder skipped: business tables not yet present in this tenant DB.'
            );
            return;
        }

        $this->call(SettingTableSeeder::class);
    }
}
