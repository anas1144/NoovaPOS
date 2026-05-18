<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class EnsurePlatformRolesSeeder extends Seeder
{
    public function run(): void
    {
        $platformPermissions = [
            ['name' => 'manage_platform', 'display_name' => 'Manage Platform'],
            ['name' => 'manage_tenants', 'display_name' => 'Manage Tenants'],
            ['name' => 'manage_subscriptions', 'display_name' => 'Manage Subscriptions'],
            ['name' => 'manage_fbr', 'display_name' => 'Manage FBR'],
            ['name' => 'manage_offline_devices', 'display_name' => 'Manage Offline Devices'],
        ];

        foreach ($platformPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission['name'],
            ], [
                'display_name' => $permission['display_name'],
                'guard_name' => 'web',
            ]);
        }

        $superAdminRole = Role::firstOrCreate([
            'name' => Role::SUPER_ADMIN,
            'guard_name' => 'web',
        ], [
            'display_name' => 'Platform Super Admin',
        ]);

        Role::firstOrCreate([
            'name' => Role::TENANT_OWNER,
            'guard_name' => 'web',
        ], [
            'display_name' => 'Tenant Owner',
        ]);

        $superAdminRole->syncPermissions(Permission::pluck('name', 'id'));

        User::role(Role::ADMIN)->get()->each(function (User $user) use ($superAdminRole) {
            if (!$user->hasRole(Role::SUPER_ADMIN)) {
                $user->assignRole($superAdminRole);
            }
        });
    }
}
