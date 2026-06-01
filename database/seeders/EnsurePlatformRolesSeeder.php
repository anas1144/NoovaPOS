<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class EnsurePlatformRolesSeeder extends Seeder
{
    /**
     * Ensure platform-level roles exist and superadmin has ALL permissions.
     * Must run AFTER DefaultPermissionsSeeder and DefaultRoleSeeder.
     */
    public function run(): void
    {
        // Ensure platform roles exist (idempotent)
        $superAdminRole = Role::firstOrCreate(
            ['name' => Role::SUPER_ADMIN, 'guard_name' => 'web'],
            ['display_name' => 'Platform Super Admin']
        );

        Role::firstOrCreate(
            ['name' => Role::TENANT_OWNER, 'guard_name' => 'web'],
            ['display_name' => 'Tenant Owner']
        );

        // Give superadmin role ALL permissions
        $superAdminRole->syncPermissions(Permission::where('guard_name', 'web')->get());

        // Ensure the seeded superadmin user actually has the role
        $superAdmin = User::where('email', 'superadmin@noovapos-gls.com')->first();
        if ($superAdmin && ! $superAdmin->hasRole(Role::SUPER_ADMIN)) {
            $superAdmin->assignRole($superAdminRole);
        }
    }
}
