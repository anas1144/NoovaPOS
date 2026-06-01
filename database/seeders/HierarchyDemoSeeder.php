<?php

namespace Database\Seeders;

use App\Models\MultiTenant;
use App\Models\Role;
use App\Models\Shop;
use App\Models\Store;
use App\Models\User;
use App\Models\UserShop;
use App\Models\UserStore;
use App\Repositories\StoreRepository;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Seeds the full NoovaPOS demo hierarchy:
 *
 * Platform Super Admin                (superadmin@noovapos-gls.com)
 *   └── Tenant: ABC Group             (tenant_owner@abcgroup.com / 123456)
 *         subdomain: abcgroup.noovapos.local
 *         ├── Store: Main Branch
 *         │     ├── Retail POS, Restaurant POS, Pharmacy POS, Bakery, Fashion
 *         └── Store: City Branch
 *               ├── Water Supply, Electronics, Distribution, Monthly Service, Custom
 */
class HierarchyDemoSeeder extends Seeder
{
    public function run(): void
    {
        $storeRepo = app(StoreRepository::class);
        $baseDomain = strtolower(trim((string) env('TENANT_BASE_DOMAIN', 'noovapos.local'), '.'));
        $subdomain  = 'abcgroup';
        $domain     = $subdomain . '.' . $baseDomain;

        // Skip if already seeded
        if (Domain::where('domain', $domain)->exists()) {
            $this->command->warn('HierarchyDemoSeeder already ran — skipping.');
            return;
        }

        // ── 1. Tenant record ──────────────────────────────────────────
        $tenant = MultiTenant::create(['id' => 'abc-group-demo']);

        // ── 2. Domain entry (so subdomain login works) ────────────────
        Domain::create(['domain' => $domain, 'tenant_id' => $tenant->id]);

        // ── 3. Stores ─────────────────────────────────────────────────
        $mainBranch = Store::create([
            'name'       => 'Main Branch',
            'tenant_id'  => $tenant->id,
            'status'     => true,
            'is_default' => true,
        ]);
        $tenant->update(['store_id' => $mainBranch->id]);

        $cityBranch = Store::create([
            'name'       => 'City Branch',
            'tenant_id'  => $tenant->id,
            'status'     => true,
            'is_default' => false,
        ]);

        // ── 4. Default settings for this tenant (currency, warehouse, etc.) ──
        $storeRepo->storeDefaultSettings($tenant->id);

        // ── 5. Tenant Owner ───────────────────────────────────────────
        $tenantOwner = $this->makeUser('Tenant', 'Owner', 'tenant_owner@abcgroup.com', $tenant->id, Role::TENANT_OWNER);
        UserStore::firstOrCreate(['user_id' => $tenantOwner->id, 'store_id' => $mainBranch->id]);
        UserStore::firstOrCreate(['user_id' => $tenantOwner->id, 'store_id' => $cityBranch->id]);

        // ── 6. Branch Managers ────────────────────────────────────────
        $bm1 = $this->makeUser('Branch', 'Manager', 'branch_manager@abcgroup.com', $tenant->id, Role::BRANCH_MANAGER);
        UserStore::firstOrCreate(['user_id' => $bm1->id, 'store_id' => $mainBranch->id]);

        $bm2 = $this->makeUser('Branch', 'Manager2', 'branch_manager2@abcgroup.com', $tenant->id, Role::BRANCH_MANAGER);
        UserStore::firstOrCreate(['user_id' => $bm2->id, 'store_id' => $cityBranch->id]);

        // ── 7. Shops — Main Branch ────────────────────────────────────
        $this->seedShop($mainBranch, $tenant->id, 'Retail Counter',      'retail', [
            [Role::SHOP_MANAGER,     'Retail Manager',    'retail.manager@abcgroup.com'],
            [Role::CASHIER,           'Retail Cashier',    'retail.cashier@abcgroup.com'],
            [Role::INVENTORY_MANAGER, 'Retail Inventory',  'retail.inventory@abcgroup.com'],
        ]);
        $this->seedShop($mainBranch, $tenant->id, 'Restaurant Hall',     'restaurant', [
            [Role::SHOP_MANAGER, 'Restaurant Manager', 'restaurant.manager@abcgroup.com'],
            [Role::WAITER,        'Waiter Ali',          'waiter@abcgroup.com'],
        ]);
        $this->seedShop($mainBranch, $tenant->id, 'Pharmacy Counter',    'pharmacy', [
            [Role::CASHIER,           'Pharmacy Cashier',   'pharmacy.cashier@abcgroup.com'],
            [Role::INVENTORY_MANAGER, 'Pharmacy Inventory', 'pharmacy.inventory@abcgroup.com'],
        ]);
        $this->seedShop($mainBranch, $tenant->id, 'Bakery Counter',      'bakery', [
            [Role::CASHIER, 'Bakery Cashier', 'bakery.cashier@abcgroup.com'],
        ]);
        $this->seedShop($mainBranch, $tenant->id, 'Fashion Store',       'fashion', [
            [Role::CASHIER, 'Fashion Cashier', 'fashion.cashier@abcgroup.com'],
        ]);

        // ── 8. Shops — City Branch ────────────────────────────────────
        $this->seedShop($cityBranch, $tenant->id, 'Water Supply Center', 'water_supply', [
            [Role::SHOP_MANAGER,   'Water Manager',  'water.manager@abcgroup.com'],
            [Role::DELIVERY_STAFF, 'Water Delivery', 'water.delivery@abcgroup.com'],
        ]);
        $this->seedShop($cityBranch, $tenant->id, 'Electronics Store',   'electronics', [
            [Role::CASHIER,           'Electronics Cashier',   'electronics.cashier@abcgroup.com'],
            [Role::INVENTORY_MANAGER, 'Electronics Inventory', 'electronics.inventory@abcgroup.com'],
        ]);
        $this->seedShop($cityBranch, $tenant->id, 'Distribution Hub',    'distribution', [
            [Role::DELIVERY_STAFF,    'Distribution Driver',    'distribution.driver@abcgroup.com'],
            [Role::INVENTORY_MANAGER, 'Distribution Inventory', 'distribution.inventory@abcgroup.com'],
        ]);
        $this->seedShop($cityBranch, $tenant->id, 'Monthly Services',    'monthly_service', [
            [Role::ACCOUNTANT,   'Service Accountant', 'service.accountant@abcgroup.com'],
            [Role::SHOP_MANAGER, 'Service Manager',    'service.manager@abcgroup.com'],
        ]);
        $this->seedShop($cityBranch, $tenant->id, 'Custom Business',     'custom', [
            [Role::CASHIER, 'Custom Cashier', 'custom.cashier@abcgroup.com'],
        ]);

        $this->command->info('');
        $this->command->info('✅  HierarchyDemoSeeder complete');
        $this->command->info('    Tenant domain : ' . $domain);
        $this->command->info('    Tenant Owner  : tenant_owner@abcgroup.com / 123456');
        $this->command->info('    Branch Mgr 1  : branch_manager@abcgroup.com / 123456');
        $this->command->info('    Branch Mgr 2  : branch_manager2@abcgroup.com / 123456');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function seedShop(Store $store, string $tenantId, string $name, string $type, array $users): void
    {
        $shop = Shop::firstOrCreate(
            ['store_id' => $store->id, 'name' => $name, 'tenant_id' => $tenantId],
            [
                'code'            => strtoupper(substr($type, 0, 3)) . '-' . $store->id,
                'shop_type'       => $type,
                'enabled_modules' => $this->modulesForType($type),
                'status'          => true,
                'tenant_id'       => $tenantId,
            ]
        );

        // POS registers are opened by cashiers on first login

        foreach ($users as [$roleName, $fullName, $email]) {
            [$firstName, $lastName] = array_pad(explode(' ', $fullName, 2), 2, '');
            $user = $this->makeUser($firstName, $lastName, $email, $tenantId, $roleName);
            UserStore::firstOrCreate(['user_id' => $user->id, 'store_id' => $store->id]);
            UserShop::firstOrCreate(['user_id'  => $user->id, 'shop_id'  => $shop->id]);
        }
    }

    private function makeUser(string $first, string $last, string $email, string $tenantId, string $role): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'first_name'        => $first,
                'last_name'         => $last,
                'email_verified_at' => Carbon::now(),
                'password'          => Hash::make('123456'),
                'status'            => 1,
                'tenant_id'         => $tenantId,
            ]
        );

        $r = Role::whereName($role)->first();
        if ($r && ! $user->hasRole($role)) {
            $user->assignRole($r);
        }

        return $user;
    }

    private function modulesForType(string $type): array
    {
        $map = [
            'retail'          => ['pos', 'inventory', 'sales', 'purchases', 'reports'],
            'restaurant'      => ['pos', 'tables', 'kot', 'kitchen_display', 'sales', 'reports'],
            'pharmacy'        => ['pos', 'inventory', 'expiry', 'sales', 'reports'],
            'bakery'          => ['pos', 'inventory', 'sales', 'reports'],
            'fashion'         => ['pos', 'inventory', 'variations', 'sales', 'reports'],
            'water_supply'    => ['pos', 'recurring', 'delivery', 'inventory', 'reports'],
            'electronics'     => ['pos', 'inventory', 'serial', 'sales', 'purchases', 'reports'],
            'distribution'    => ['pos', 'inventory', 'transfers', 'delivery', 'reports'],
            'monthly_service' => ['pos', 'subscriptions', 'invoicing', 'reports'],
            'custom'          => ['pos', 'inventory', 'sales', 'reports'],
        ];
        return $map[$type] ?? ['pos', 'inventory', 'sales', 'reports'];
    }
}
