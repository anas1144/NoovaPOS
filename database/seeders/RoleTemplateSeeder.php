<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Permission;
use App\Models\RoleTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds default (system) role templates per shop type so tenant owners have
 * something to pick from out of the box. Run: php artisan db:seed --class=RoleTemplateSeeder
 *
 * Only permissions that actually exist are attached, so this is safe to run on
 * any permission set.
 */
class RoleTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $allNames = Permission::pluck('name')->all();
        $manage   = array_values(array_filter($allNames, fn ($n) => Str::startsWith($n, 'manage_')));

        // Helper: expand a list of manage_* into their view/create/edit/delete children that exist.
        $expand = function (array $manageNames) use ($allNames) {
            $out = [];
            foreach ($manageNames as $m) {
                $out[] = $m;
                $module = Str::after($m, 'manage_');
                foreach (['view', 'create', 'edit', 'delete'] as $action) {
                    $child = "{$action}_{$module}";
                    if (in_array($child, $allNames, true)) {
                        $out[] = $child;
                    }
                }
            }
            return array_values(array_unique($out));
        };

        // Cashier-ish permission subset (only those that exist).
        $cashierManage = array_values(array_intersect($manage, [
            'manage_pos_screen', 'manage_sale', 'manage_sale_return', 'manage_products',
        ]));

        // Only-keep-existing helper for explicit permission lists.
        $only = fn (array $names) => array_values(array_intersect($names, $allNames));

        // Waiter: POS + sales (take orders, send to kitchen) — read products.
        $waiterPerms = $only([
            'manage_pos_screen', 'view_pos_screen',
            'manage_sale', 'view_sale', 'create_sale',
            'view_products', 'view_product', 'view_customers', 'view_customer',
        ]);

        // Kitchen: back-of-house, read-only on the KOT queue / products.
        $kitchenPerms = $only([
            'view_pos_screen', 'view_sale', 'view_products', 'view_product', 'view_dashboard',
        ]);

        // Delivery boy: see assigned deliveries + their customers only.
        $deliveryPerms = $only([
            'view_sale', 'view_sales', 'view_customer', 'view_customers',
            'view_deliveries', 'view_delivery', 'view_dashboard',
        ]);

        foreach (array_keys(Plan::SHOP_TYPES) as $shopType) {
            // Manager template: everything the tenant can manage.
            RoleTemplate::updateOrCreate(
                ['name' => 'shop_manager', 'shop_type' => $shopType],
                [
                    'display_name' => 'Shop Manager',
                    'permissions'  => $expand($manage),
                    'is_system'    => true,
                ]
            );

            // Cashier template: POS + sales focused.
            RoleTemplate::updateOrCreate(
                ['name' => 'cashier', 'shop_type' => $shopType],
                [
                    'display_name' => 'Cashier',
                    'permissions'  => $expand($cashierManage ?: ['manage_pos_screen']),
                    'is_system'    => true,
                ]
            );

            // Restaurant shops get front- and back-of-house roles.
            if ($shopType === 'restaurant') {
                RoleTemplate::updateOrCreate(
                    ['name' => 'waiter', 'shop_type' => $shopType],
                    ['display_name' => 'Waiter', 'permissions' => $waiterPerms, 'is_system' => true]
                );
                RoleTemplate::updateOrCreate(
                    ['name' => 'kitchen', 'shop_type' => $shopType],
                    ['display_name' => 'Kitchen', 'permissions' => $kitchenPerms, 'is_system' => true]
                );
            }

            // Shops that fulfil orders get a delivery-boy template.
            if (in_array($shopType, ['restaurant', 'water_supply', 'distribution'], true)) {
                RoleTemplate::updateOrCreate(
                    ['name' => 'delivery_boy', 'shop_type' => $shopType],
                    ['display_name' => 'Delivery Boy', 'permissions' => $deliveryPerms, 'is_system' => true]
                );
            }
        }
    }
}
