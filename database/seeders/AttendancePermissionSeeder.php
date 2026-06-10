<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

/**
 * Attendance module permissions, plus role grants:
 *  - super admin / admin / tenant owner : all
 *  - branch & shop managers             : view/dashboard/manual/edit + tasks + reports
 *  - every other role                   : self check-in/out + view + tasks view
 */
class AttendancePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Core
            'attendance.view', 'attendance.dashboard', 'attendance.checkin',
            'attendance.checkout', 'attendance.manual', 'attendance.edit', 'attendance.delete',
            // Tasks
            'attendance.task.view', 'attendance.task.create', 'attendance.task.update', 'attendance.task.delete',
            // Biometrics
            'attendance.face.view', 'attendance.face.enroll', 'attendance.face.delete',
            'attendance.fingerprint.view', 'attendance.fingerprint.enroll', 'attendance.fingerprint.delete',
            // Reports
            'attendance.report.view', 'attendance.report.export',
            // Settings
            'attendance.settings.view', 'attendance.settings.update',
            // No-code device connectors
            'attendance.device.view', 'attendance.device.manage',
            // Requests (corrections / leave)
            'attendance.request.view', 'attendance.request.manage',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['display_name' => Str::headline(str_replace('.', ' ', $name))]
            );
        }

        // Full access roles.
        foreach ([Role::SUPER_ADMIN, Role::ADMIN, Role::TENANT_OWNER] as $roleName) {
            if ($role = Role::whereName($roleName)->first()) {
                $role->givePermissionTo($permissions);
            }
        }

        // Managers: everything except settings/delete biometrics.
        $managerPerms = array_values(array_diff($permissions, [
            'attendance.settings.update',
        ]));
        foreach ([Role::BRANCH_MANAGER, Role::SHOP_MANAGER] as $roleName) {
            if ($role = Role::whereName($roleName)->first()) {
                $role->givePermissionTo($managerPerms);
            }
        }

        // Everyone else who clocks in: self check-in/out, view, see tasks.
        $selfPerms = [
            'attendance.view', 'attendance.checkin', 'attendance.checkout',
            'attendance.task.view', 'attendance.task.create', 'attendance.task.update',
            'attendance.request.view',
        ];
        foreach ([
            Role::CASHIER, Role::WAITER, Role::KITCHEN, Role::ACCOUNTANT,
            Role::INVENTORY_MANAGER, Role::DELIVERY_STAFF, Role::DELIVERY_BOY,
        ] as $roleName) {
            if ($role = Role::whereName($roleName)->first()) {
                $role->givePermissionTo($selfPerms);
            }
        }
    }
}
