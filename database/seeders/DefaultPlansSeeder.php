<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Seeder;

/**
 * Plans = one per shop type. A tenant subscribes to one (or several — one per
 * shop type) and each subscription auto-provisions a store of that type.
 *
 * Pricing: base per-shop price + the type's surcharge, in USD (global) and PKR
 * (Pakistan), monthly and yearly (~2 months free on yearly).
 */
class DefaultPlansSeeder extends Seeder
{
    // Per-shop type surcharges (USD)
    const SHOP_SURCHARGES_USD = [
        'retail'          => 0,
        'restaurant'      => 5,
        'pharmacy'        => 3,
        'water_supply'    => 4,
        'bakery'          => 2,
        'electronics'     => 2,
        'fashion'         => 2,
        'distribution'    => 5,
        'monthly_service' => 3,
        'custom'          => 5,
    ];

    // Per-shop type surcharges (PKR)
    const SHOP_SURCHARGES_PKR = [
        'retail'          => 0,
        'restaurant'      => 1400,
        'pharmacy'        => 840,
        'water_supply'    => 1120,
        'bakery'          => 560,
        'electronics'     => 560,
        'fashion'         => 560,
        'distribution'    => 1400,
        'monthly_service' => 840,
        'custom'          => 1400,
    ];

    public function run(): void
    {
        // ── ONE PLAN PER SHOP TYPE ───────────────────────────────────────
        $sort = 1;
        $keepSlugs = [];
        foreach (Plan::SHOP_TYPES as $key => $label) {
            $slug = 'type-' . str_replace('_', '-', $key);
            $keepSlugs[] = $slug;

            $usd = 10 + (self::SHOP_SURCHARGES_USD[$key] ?? 0);
            $pkr = 2800 + (self::SHOP_SURCHARGES_PKR[$key] ?? 0);
            $modules = Plan::SHOP_TYPE_MODULES[$key] ?? ['pos', 'sales', 'reports'];

            Plan::updateOrCreate(
                ['slug' => $slug],
                [
                    'name'                  => $label . ' Plan',
                    'shop_type'             => $key,
                    'description'           => 'Subscription for one ' . strtolower($label) . ' store. Includes the modules that business type needs.',
                    'price'                 => $usd,
                    'currency'              => 'USD',
                    'price_pkr'             => $pkr,
                    'price_yearly'          => $usd * 10,        // ~2 months free
                    'price_yearly_pkr'      => $pkr * 10,
                    'billing_cycle'         => 'monthly',
                    'trial_days'            => 14,
                    'max_stores'            => 1,
                    'max_shops'             => 3,
                    'max_registers'         => 3,
                    'max_users'             => 5,
                    'max_products'          => 5000,
                    'features'              => $modules,
                    'shop_type_pricing'     => [],
                    'shop_type_pricing_pkr' => [],
                    'status'                => true,
                    'is_custom'             => false,
                    'is_contact_sales'      => false,
                    'is_featured'           => $key === 'retail',
                    'sort_order'            => $sort++,
                    'allowed_countries'     => null,
                ]
            );
        }

        // ── Remove the old generic tier plans (Starter/Growth/…) that are not
        // per-shop-type, as long as nothing is subscribed to them.
        $stale = Plan::query()->whereNotIn('slug', $keepSlugs)->get();
        foreach ($stale as $plan) {
            if (! Subscription::query()->where('plan_id', $plan->id)->exists()) {
                $plan->delete();
            }
        }
    }
}
