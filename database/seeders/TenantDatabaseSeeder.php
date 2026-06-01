<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Used by: php artisan tenants:seed
 *
 * Seeds the TENANT-scoped data only.
 * Permissions, roles, and plans live in the central DB — do NOT re-seed them here.
 * SettingTableSeeder creates: currencies, default warehouse, walk-in customer, settings.
 */
class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SettingTableSeeder::class);
    }
}
