<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Central database seed order.
     *
     * Rules:
     *  - Roles MUST run before permission seeders that sync permissions to roles
     *  - DefaultPermissionsSeeder MUST run before EnsurePlatformRolesSeeder
     *  - SettingTableSeeder MUST run before HierarchyDemoSeeder (storeDefaultSettings needs Currency)
     *
     * Superadmin: superadmin@noovapos-gls.com / 123456
     * Demo tenant: tenant_owner@abcgroup.com / 123456  → abcgroup.noovapos.local
     */
    public function run(): void
    {
        // ── 1. Roles first (legacy permission seeders call Role::whereName()->syncPermissions)
        $this->call(DefaultRoleSeeder::class);

        // ── 2. All permissions (firstOrCreate with guard_name=web)
        $this->call(DefaultPermissionsSeeder::class);
        $this->call(AddDashboardAndSettingPermissionsSeeder::class);
        $this->call(AddPurchaseAndSalePermissionsSeeder::class);
        $this->call(AddPurchaseReturnAndSaleReturnPermissionsSeeder::class);
        $this->call(GenerateCrudPermissionsSeeder::class);
        $this->call(AttendancePermissionSeeder::class);
        $this->call(FbrInvoicePermissionSeeder::class);
        $this->call(FbrErrorCodeSeeder::class);
        $this->call(FbrSandboxScenarioSeeder::class);

        // ── 3. Platform super-admin user + sync ALL permissions to superadmin role
        $this->call(DefaultUserSeeder::class);
        $this->call(EnsurePlatformRolesSeeder::class);

        // ── 4. Base data — currencies, warehouse, walk-in customer, app settings
        $this->call(SettingTableSeeder::class);

        // ── 5. SaaS subscription plans
        $this->call(DefaultPlansSeeder::class);

        // ── 5b. Super-admin role+permission templates per shop type
        $this->call(RoleTemplateSeeder::class);

        // ── 5c. Shop-type registry (super admin enables/disables)
        $this->call(ShopTypeSeeder::class);

        // ── 5d. Platform defaults: billing settings + payout bank accounts
        $this->call(PlatformDefaultsSeeder::class);

        // ── 5e. Starter marketing CMS content (landing + shop-type + FBR pages)
        $this->call(CmsSeeder::class);

        // ── 6. Demo tenant hierarchy (local / dev only)
        if (app()->environment(['local', 'development', 'testing'])) {
            $this->call(HierarchyDemoSeeder::class);
        }
    }
}
