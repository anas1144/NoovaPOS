<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

/**
 * FBR Digital Invoice module permissions + the fbr_agent role.
 *
 * Sync is gated behind its own permission (fbr_invoice_sync) so only authorised
 * users push invoices to FBR.
 */
class FbrInvoicePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'fbr_invoice_create', 'fbr_invoice_edit', 'fbr_invoice_delete', 'fbr_invoice_view',
            'fbr_invoice_sync', 'fbr_invoice_print', 'fbr_invoice_reports',
            'fbr_invoice_errors', 'fbr_invoice_testing',
            // business / agent management
            'fbr_business_manage', 'fbr_agent_manage',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['display_name' => Str::headline(str_replace('_', ' ', $name))]
            );
        }

        // Ensure the fbr_agent role exists.
        Role::firstOrCreate(
            ['name' => Role::FBR_AGENT, 'guard_name' => 'web'],
            ['display_name' => 'FBR Agent']
        );

        // Full access: super admin / admin / tenant owner / agent.
        foreach ([Role::SUPER_ADMIN, Role::ADMIN, Role::TENANT_OWNER, Role::FBR_AGENT] as $roleName) {
            if ($role = Role::whereName($roleName)->first()) {
                $role->givePermissionTo($permissions);
            }
        }

        // Accountant: create/view/print/reports + sync, no delete/agent admin.
        if ($acc = Role::whereName(Role::ACCOUNTANT)->first()) {
            $acc->givePermissionTo([
                'fbr_invoice_create', 'fbr_invoice_edit', 'fbr_invoice_view',
                'fbr_invoice_sync', 'fbr_invoice_print', 'fbr_invoice_reports', 'fbr_invoice_errors',
            ]);
        }
    }
}
