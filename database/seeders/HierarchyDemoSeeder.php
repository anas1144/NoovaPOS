<?php

namespace Database\Seeders;

use App\Models\MultiTenant;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Shop;
use App\Models\Store;
use App\Models\User;
use App\Models\UserShop;
use App\Models\UserStore;
use App\Repositories\StoreRepository;
use App\Services\TenantSubscriptionService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Seeds the demo tenant "ABC Group" entirely from PLANS:
 *
 * Platform Super Admin          (superadmin@noovapos-gls.com)
 *   └── Tenant: ABC Group       (tenant_owner@abcgroup.com / 123456)
 *         subdomain: abcgroup.noovapos.local
 *         └── Subscribes to ALL per-shop-type plans → one store per type
 *             (Retail, Restaurant, Pharmacy, Water Supply, Bakery, Electronics,
 *              Fashion, Distribution, Monthly Service, FBR Digital, Custom),
 *             each provisioned by its subscription. A shop + demo staff are
 *             added under each store for role testing.
 */
class HierarchyDemoSeeder extends Seeder
{
    /** Demo staff per shop type: [roleName, "First Last", email]. */
    private function staffFor(string $type): array
    {
        $map = [
            'retail' => [
                [Role::SHOP_MANAGER,      'Retail Manager',   'retail.manager@abcgroup.com'],
                [Role::CASHIER,           'Retail Cashier',   'retail.cashier@abcgroup.com'],
                [Role::INVENTORY_MANAGER, 'Retail Inventory', 'retail.inventory@abcgroup.com'],
            ],
            'restaurant' => [
                [Role::SHOP_MANAGER, 'Restaurant Manager', 'restaurant.manager@abcgroup.com'],
                [Role::WAITER,       'Waiter Ali',         'waiter@abcgroup.com'],
                [Role::KITCHEN,      'Kitchen Chef',       'kitchen@abcgroup.com'],
                [Role::DELIVERY_BOY, 'Delivery Boy',       'delivery.boy@abcgroup.com'],
            ],
            'pharmacy' => [
                [Role::CASHIER,           'Pharmacy Cashier',   'pharmacy.cashier@abcgroup.com'],
                [Role::INVENTORY_MANAGER, 'Pharmacy Inventory', 'pharmacy.inventory@abcgroup.com'],
            ],
            'water_supply' => [
                [Role::SHOP_MANAGER,   'Water Manager',  'water.manager@abcgroup.com'],
                [Role::DELIVERY_STAFF, 'Water Delivery', 'water.delivery@abcgroup.com'],
            ],
            'bakery' => [
                [Role::CASHIER, 'Bakery Cashier', 'bakery.cashier@abcgroup.com'],
            ],
            'electronics' => [
                [Role::CASHIER,           'Electronics Cashier',   'electronics.cashier@abcgroup.com'],
                [Role::INVENTORY_MANAGER, 'Electronics Inventory', 'electronics.inventory@abcgroup.com'],
            ],
            'fashion' => [
                [Role::CASHIER, 'Fashion Cashier', 'fashion.cashier@abcgroup.com'],
            ],
            'distribution' => [
                [Role::DELIVERY_STAFF,    'Distribution Driver',    'distribution.driver@abcgroup.com'],
                [Role::INVENTORY_MANAGER, 'Distribution Inventory', 'distribution.inventory@abcgroup.com'],
            ],
            'monthly_service' => [
                [Role::ACCOUNTANT,   'Service Accountant', 'service.accountant@abcgroup.com'],
                [Role::SHOP_MANAGER, 'Service Manager',    'service.manager@abcgroup.com'],
            ],
            'custom' => [
                [Role::CASHIER, 'Custom Cashier', 'custom.cashier@abcgroup.com'],
            ],
            // fbr_digital has no POS billing — no shop staff; the owner manages it.
            'fbr_digital' => [],
        ];

        return $map[$type] ?? [];
    }

    public function run(): void
    {
        $storeRepo = app(StoreRepository::class);
        $subSvc    = app(TenantSubscriptionService::class);
        $baseDomain = strtolower(trim((string) env('TENANT_BASE_DOMAIN', 'noovapos.local'), '.'));
        $domain     = 'abcgroup.' . $baseDomain;

        if (Domain::where('domain', $domain)->exists()) {
            $this->command->warn('HierarchyDemoSeeder already ran — skipping.');
            return;
        }

        // ── Tenant + domain + owner ───────────────────────────────────────
        $tenant = MultiTenant::create(['id' => 'abc-group-demo']);
        Domain::create(['domain' => $domain, 'tenant_id' => $tenant->id]);

        $tenantOwner = $this->makeUser('Tenant', 'Owner', 'tenant_owner@abcgroup.com', $tenant->id, Role::TENANT_OWNER);
        $tenantOwner->update(['country' => 'PK']); // exercise FBR + PK pricing

        // ── Subscribe to EVERY per-shop-type plan → one store per type ────
        $plans = Plan::query()
            ->where('status', true)
            ->whereNotNull('shop_type')
            ->orderBy('sort_order')
            ->get();

        if ($plans->isEmpty()) {
            $this->command->warn('No per-shop-type plans found — run DefaultPlansSeeder first.');
            return;
        }

        $stores = [];   // shop_type => Store
        $first = true;
        foreach ($plans as $plan) {
            $label = Plan::SHOP_TYPES[$plan->shop_type] ?? ucfirst(str_replace('_', ' ', $plan->shop_type));
            $res = $subSvc->subscribeToPlan($tenant, $plan, $tenantOwner, $first, $label . ' Branch');
            $store = $res['store'];
            if ($first) {
                $tenant->update(['store_id' => $store->id]);
                $first = false;
            }
            $stores[$plan->shop_type] = $store;
        }

        // ── Tenant defaults (currency, warehouse, walk-in customer, settings)
        $storeRepo->storeDefaultSettings($tenant->id);

        // ── A branch manager across all stores ────────────────────────────
        $bm = $this->makeUser('Branch', 'Manager', 'branch_manager@abcgroup.com', $tenant->id, Role::BRANCH_MANAGER);
        foreach ($stores as $store) {
            UserStore::firstOrCreate(['user_id' => $bm->id, 'store_id' => $store->id]);
        }

        // ── A shop + demo staff under each store ──────────────────────────
        foreach ($stores as $type => $store) {
            $label = Plan::SHOP_TYPES[$type] ?? ucfirst(str_replace('_', ' ', $type));
            $this->seedShop($store, $tenant->id, $label . ' Counter', $type, $this->staffFor($type));
        }

        $this->command->info('');
        $this->command->info('✅  HierarchyDemoSeeder complete (plan-based)');
        $this->command->info('    Tenant domain : ' . $domain);
        $this->command->info('    Owner         : tenant_owner@abcgroup.com / 123456');
        $this->command->info('    Branch Mgr    : branch_manager@abcgroup.com / 123456');
        $this->command->info('    Plans/Stores  : ' . $plans->count() . ' (one per shop type)');
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
            'fbr_digital'     => ['fbr_invoices', 'fbr_sync', 'fbr_reports'],
            'custom'          => ['pos', 'inventory', 'sales', 'reports'],
        ];
        return $map[$type] ?? ['pos', 'inventory', 'sales', 'reports'];
    }
}
