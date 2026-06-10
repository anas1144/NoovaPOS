<?php

namespace Database\Seeders;

use App\Models\Role as AppRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DefaultRoleSeeder extends Seeder
{
    /**
     * Seed all roles matching the NoovaPOS hierarchy:
     *
     * Platform Super Admin
     *   → Tenant Owner / Admin        (Tenant / Company level)
     *       → Branch Manager          (Store / Branch level)
     *           → Shop Manager        (Shop / POS Counter level)
     *           → Cashier             (Register level)
     *           → Waiter              (Restaurant shops)
     *           → Accountant          (Finance access)
     *           → Inventory Manager   (Stock access)
     *           → Delivery Staff      (Water / Delivery shops)
     */
    public function run(): void
    {
        $roles = [
            // ── Platform level ─────────────────────────────────────────
            [
                'name'         => AppRole::SUPER_ADMIN,
                'display_name' => 'Platform Super Admin',
            ],

            // ── Tenant / Company level ──────────────────────────────────
            [
                'name'         => AppRole::ADMIN,
                'display_name' => 'Admin',
            ],
            [
                'name'         => AppRole::TENANT_OWNER,
                'display_name' => 'Tenant Owner',
            ],

            // ── Store / Branch level ────────────────────────────────────
            [
                'name'         => AppRole::BRANCH_MANAGER,
                'display_name' => 'Branch Manager',
            ],

            // ── Shop / POS Counter level ────────────────────────────────
            [
                'name'         => AppRole::SHOP_MANAGER,
                'display_name' => 'Shop Manager',
            ],
            [
                'name'         => AppRole::CASHIER,
                'display_name' => 'Cashier',
            ],
            [
                'name'         => AppRole::WAITER,
                'display_name' => 'Waiter',
            ],
            [
                'name'         => AppRole::ACCOUNTANT,
                'display_name' => 'Accountant',
            ],
            [
                'name'         => AppRole::INVENTORY_MANAGER,
                'display_name' => 'Inventory Manager',
            ],
            [
                'name'         => AppRole::DELIVERY_STAFF,
                'display_name' => 'Delivery Staff',
            ],
            [
                'name'         => AppRole::KITCHEN,
                'display_name' => 'Kitchen',
            ],
            [
                'name'         => AppRole::DELIVERY_BOY,
                'display_name' => 'Delivery Boy',
            ],
        ];

        // Create roles that do not exist yet
        foreach ($roles as $attributes) {
            Role::firstOrCreate(
                ['name' => $attributes['name'], 'guard_name' => 'web'],
                ['display_name' => $attributes['display_name']]
            );
        }

        $allPermissions = Permission::pluck('name', 'id');

        // Full permissions: platform super admin, admin, tenant owner
        foreach ([AppRole::SUPER_ADMIN, AppRole::ADMIN, AppRole::TENANT_OWNER] as $roleName) {
            $role = Role::whereName($roleName)->first();
            if ($role) {
                $role->syncPermissions($allPermissions);
            }
        }

        // Branch manager – all except platform-level permissions
        $branchPermissions = Permission::where('name', 'not like', 'manage_platform%')
            ->where('name', 'not like', 'manage_tenants%')
            ->where('name', 'not like', 'manage_subscriptions%')
            ->pluck('name', 'id');

        $branchRole = Role::whereName(AppRole::BRANCH_MANAGER)->first();
        if ($branchRole) {
            $branchRole->syncPermissions($branchPermissions);
        }

        // Shop manager – sales, stock, customers, reports, products
        $shopManagerPermissions = Permission::where(function ($q) {
            $q->where('name', 'like', '%sales%')
              ->orWhere('name', 'like', '%sale%')
              ->orWhere('name', 'like', '%product%')
              ->orWhere('name', 'like', '%customer%')
              ->orWhere('name', 'like', '%stock%')
              ->orWhere('name', 'like', '%report%')
              ->orWhere('name', 'like', '%purchase%')
              ->orWhere('name', 'like', '%quotation%')
              ->orWhere('name', 'like', '%expense%')
              ->orWhere('name', 'like', '%warehouse%');
        })->pluck('name', 'id');

        $shopManagerRole = Role::whereName(AppRole::SHOP_MANAGER)->first();
        if ($shopManagerRole) {
            $shopManagerRole->syncPermissions($shopManagerPermissions);
        }

        // Cashier – POS sales only
        $cashierPermissions = Permission::where(function ($q) {
            $q->where('name', 'like', '%sale%')
              ->orWhere('name', 'like', '%customer%')
              ->orWhere('name', 'like', 'view_product%')
              ->orWhere('name', 'like', 'view_dashboard%');
        })->pluck('name', 'id');

        $cashierRole = Role::whereName(AppRole::CASHIER)->first();
        if ($cashierRole) {
            $cashierRole->syncPermissions($cashierPermissions);
        }

        // Waiter – restaurant: view products, create sales, manage KOT
        $waiterPermissions = Permission::where(function ($q) {
            $q->where('name', 'like', 'view_product%')
              ->orWhere('name', 'like', '%sale%')
              ->orWhere('name', 'like', 'view_customer%');
        })->pluck('name', 'id');

        $waiterRole = Role::whereName(AppRole::WAITER)->first();
        if ($waiterRole) {
            $waiterRole->syncPermissions($waiterPermissions);
        }

        // Accountant – finance / reports / purchases / expenses
        $accountantPermissions = Permission::where(function ($q) {
            $q->where('name', 'like', '%expense%')
              ->orWhere('name', 'like', '%purchase%')
              ->orWhere('name', 'like', '%report%')
              ->orWhere('name', 'like', '%sale%')
              ->orWhere('name', 'like', 'view_dashboard%');
        })->pluck('name', 'id');

        $accountantRole = Role::whereName(AppRole::ACCOUNTANT)->first();
        if ($accountantRole) {
            $accountantRole->syncPermissions($accountantPermissions);
        }

        // Inventory manager – stock, warehouse, products, transfers, adjustments
        $inventoryPermissions = Permission::where(function ($q) {
            $q->where('name', 'like', '%product%')
              ->orWhere('name', 'like', '%stock%')
              ->orWhere('name', 'like', '%warehouse%')
              ->orWhere('name', 'like', '%transfer%')
              ->orWhere('name', 'like', '%adjustment%')
              ->orWhere('name', 'like', '%purchase%')
              ->orWhere('name', 'like', '%supplier%');
        })->pluck('name', 'id');

        $inventoryRole = Role::whereName(AppRole::INVENTORY_MANAGER)->first();
        if ($inventoryRole) {
            $inventoryRole->syncPermissions($inventoryPermissions);
        }

        // Delivery staff – minimal: view assigned deliveries only
        $deliveryPermissions = Permission::where('name', 'like', 'view_%')
            ->where(function ($q) {
                $q->where('name', 'like', '%sale%')
                  ->orWhere('name', 'like', '%customer%')
                  ->orWhere('name', 'like', 'view_dashboard%');
            })->pluck('name', 'id');

        $deliveryRole = Role::whereName(AppRole::DELIVERY_STAFF)->first();
        if ($deliveryRole) {
            $deliveryRole->syncPermissions($deliveryPermissions);
        }

        // Kitchen – restaurant back-of-house: see the KOT queue / kitchen display
        // and the products being prepared. Read-only on sales (no checkout).
        $kitchenPermissions = Permission::where(function ($q) {
            $q->where('name', 'like', 'view_product%')
              ->orWhere('name', 'like', 'view_sale%')
              ->orWhere('name', 'like', 'view_dashboard%');
        })->pluck('name', 'id');

        $kitchenRole = Role::whereName(AppRole::KITCHEN)->first();
        if ($kitchenRole) {
            $kitchenRole->syncPermissions($kitchenPermissions);
        }

        // Delivery boy – the rider who fulfils takeaway/delivery orders: view
        // assigned deliveries + their customers only.
        $deliveryBoyPermissions = Permission::where('name', 'like', 'view_%')
            ->where(function ($q) {
                $q->where('name', 'like', '%sale%')
                  ->orWhere('name', 'like', '%customer%')
                  ->orWhere('name', 'like', '%deliver%')
                  ->orWhere('name', 'like', 'view_dashboard%');
            })->pluck('name', 'id');

        $deliveryBoyRole = Role::whereName(AppRole::DELIVERY_BOY)->first();
        if ($deliveryBoyRole) {
            $deliveryBoyRole->syncPermissions($deliveryBoyPermissions);
        }
    }
}
