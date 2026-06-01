<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DefaultPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // All permissions MUST have guard_name='web' for Spatie to match them.
        // firstOrCreate so re-running is always safe.
        $permissions = [
            // Platform / Super-admin
            ['name' => 'manage_platform',        'display_name' => 'Manage Platform'],
            ['name' => 'manage_tenants',         'display_name' => 'Manage Tenants'],
            ['name' => 'manage_subscriptions',   'display_name' => 'Manage Subscriptions'],
            ['name' => 'manage_fbr',             'display_name' => 'Manage FBR'],
            ['name' => 'manage_offline_devices', 'display_name' => 'Manage Offline Devices'],
            // User & role management
            ['name' => 'manage_roles',           'display_name' => 'Manage Roles'],
            ['name' => 'manage_users',           'display_name' => 'Manage Users'],
            // Catalogue & inventory
            ['name' => 'manage_brands',             'display_name' => 'Manage Brands'],
            ['name' => 'manage_currency',           'display_name' => 'Manage Currency'],
            ['name' => 'manage_warehouses',         'display_name' => 'Manage Warehouses'],
            ['name' => 'manage_units',              'display_name' => 'Manage Units'],
            ['name' => 'manage_product_categories', 'display_name' => 'Manage Product Categories'],
            ['name' => 'manage_products',           'display_name' => 'Manage Products'],
            ['name' => 'manage_suppliers',          'display_name' => 'Manage Suppliers'],
            ['name' => 'manage_customers',          'display_name' => 'Manage Customers'],
            // Store operations
            ['name' => 'manage_shops',     'display_name' => 'Manage Shops'],
            ['name' => 'manage_store',     'display_name' => 'Manage Store'],
            ['name' => 'manage_dashboard', 'display_name' => 'Manage Dashboard'],
            // POS & sales
            ['name' => 'manage_pos_screen',  'display_name' => 'Manage POS Screen'],
            ['name' => 'manage_sale',        'display_name' => 'Manage Sales'],
            ['name' => 'manage_sale_return', 'display_name' => 'Manage Sale Returns'],
            ['name' => 'manage_quotations',  'display_name' => 'Manage Quotations'],
            ['name' => 'manage_hold',        'display_name' => 'Manage Hold Sales'],
            ['name' => 'manage_print_barcode','display_name'=> 'Manage Print Barcode'],
            // Purchasing
            ['name' => 'manage_purchase',        'display_name' => 'Manage Purchases'],
            ['name' => 'manage_purchase_return', 'display_name' => 'Manage Purchase Returns'],
            // Stock & transfers
            ['name' => 'manage_adjustments', 'display_name' => 'Manage Adjustments'],
            ['name' => 'manage_transfers',   'display_name' => 'Manage Transfers'],
            // Finance
            ['name' => 'manage_expenses',           'display_name' => 'Manage Expenses'],
            ['name' => 'manage_expense_categories', 'display_name' => 'Manage Expense Categories'],
            // Reports
            ['name' => 'manage_report',  'display_name' => 'Manage Reports'],
            ['name' => 'manage_reports', 'display_name' => 'Manage Reports (all)'],
            ['name' => 'edit_reports',   'display_name' => 'Edit Reports'],
            // Settings
            ['name' => 'manage_setting',         'display_name' => 'Manage Settings'],
            ['name' => 'manage_language',        'display_name' => 'Manage Languages'],
            ['name' => 'manage_sms_apis',        'display_name' => 'Manage SMS APIs'],
            ['name' => 'edit_sms_apis',          'display_name' => 'Edit SMS APIs'],
            ['name' => 'view_sms_apis',          'display_name' => 'View SMS APIs'],
            ['name' => 'manage_sms_templates',   'display_name' => 'Manage SMS Templates'],
            ['name' => 'manage_email_templates', 'display_name' => 'Manage Email Templates'],
            ['name' => 'manage_variations',      'display_name' => 'Manage Variations'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                ['display_name' => $perm['display_name']]
            );
        }
    }
}
