<?php

namespace Database\Seeders;

use App\Models\Role as AppRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DefaultRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => AppRole::SUPER_ADMIN,
                'display_name' => 'Platform Super Admin',
            ],
            [
                'name' => AppRole::ADMIN,
                'display_name' => ' Admin',
            ],
            [
                'name' => AppRole::TENANT_OWNER,
                'display_name' => 'Tenant Owner',
            ],
        ];

        foreach ($roles as $role) {
            $role = Role::whereName($role['name'])->first();
            if (empty($role)) {
                $role = Role::create($role);
            }
        }
        /** @var Role $adminRole */
        $adminRole = Role::whereName(AppRole::ADMIN)->first();
        $superAdminRole = Role::whereName(AppRole::SUPER_ADMIN)->first();
        $tenantOwnerRole = Role::whereName(AppRole::TENANT_OWNER)->first();

        $allPermissions = Permission::pluck('name', 'id');
        $adminRole->syncPermissions($allPermissions);
        $superAdminRole->syncPermissions($allPermissions);
        $tenantOwnerRole->syncPermissions($allPermissions);
    }
}
