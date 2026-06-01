<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Plans architecture:
 *
 * - All prices in USD by default; PKR prices for Pakistan.
 * - NO unlimited limits — every plan has hard caps.
 * - Default plan: 1 store per tenant, multiple shops.
 * - Shops are priced per-unit ($10 USD / Rs.2,800 PKR base per shop).
 * - Shop-type surcharges apply on top of base per-shop price.
 *   e.g. restaurant shop = base $10 + $5 surcharge = $15/shop
 * - Volume discounts: more shops = lower per-shop price.
 * - Custom plan = "Contact Sales" — no self-serve subscription.
 * - Super admin builds per-tenant custom plans with auto-calculated pricing.
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

    // Volume discount tiers (USD): min-shop-count => per-shop price
    const VOLUME_USD = [
        1  => 10.00,
        3  => 8.00,
        6  => 6.50,
        11 => 5.00,
    ];

    // Volume discount tiers (PKR)
    const VOLUME_PKR = [
        1  => 2800,
        3  => 2240,
        6  => 1820,
        11 => 1400,
    ];

    public function run(): void
    {
        $plans = [

            // ── STARTER ─────────────────────────────────────────────────
            [
                'name'                  => 'Starter',
                'slug'                  => 'starter',
                'description'           => 'Perfect for small businesses. 1 branch, up to 3 shops, 5 users. Includes POS, stock, and basic reports.',
                'price'                 => 20.00,
                'currency'              => 'USD',
                'price_pkr'             => 5600.00,
                'price_yearly'          => 192.00,
                'price_yearly_pkr'      => 53760.00,
                'billing_cycle'         => 'monthly',
                'trial_days'            => 14,
                'max_stores'            => 1,
                'max_shops'             => 3,
                'max_registers'         => 3,
                'max_users'             => 5,
                'max_products'          => 1000,
                'per_shop_price'        => 10.00,
                'per_shop_price_pkr'    => 2800.00,
                'shop_type_pricing'     => self::SHOP_SURCHARGES_USD,
                'shop_type_pricing_pkr' => self::SHOP_SURCHARGES_PKR,
                'volume_pricing'        => self::VOLUME_USD,
                'volume_pricing_pkr'    => self::VOLUME_PKR,
                'features'              => ['pos', 'stock', 'basic_reports', 'barcode', 'customers', 'suppliers'],
                'status'                => true,
                'is_custom'             => false,
                'is_contact_sales'      => false,
                'is_featured'           => false,
                'sort_order'            => 1,
                'allowed_countries'     => null,
            ],

            // ── GROWTH ──────────────────────────────────────────────────
            [
                'name'                  => 'Growth',
                'slug'                  => 'growth',
                'description'           => 'For growing businesses with multiple branches. Up to 3 stores, 10 shops, 25 users. Includes transfers, advanced reports, and offline sync.',
                'price'                 => 49.00,
                'currency'              => 'USD',
                'price_pkr'             => 13720.00,
                'price_yearly'          => 470.40,
                'price_yearly_pkr'      => 131712.00,
                'billing_cycle'         => 'monthly',
                'trial_days'            => 14,
                'max_stores'            => 3,
                'max_shops'             => 10,
                'max_registers'         => 20,
                'max_users'             => 25,
                'max_products'          => 10000,
                'per_shop_price'        => 10.00,
                'per_shop_price_pkr'    => 2800.00,
                'shop_type_pricing'     => self::SHOP_SURCHARGES_USD,
                'shop_type_pricing_pkr' => self::SHOP_SURCHARGES_PKR,
                'volume_pricing'        => self::VOLUME_USD,
                'volume_pricing_pkr'    => self::VOLUME_PKR,
                'features'              => [
                    'pos', 'stock', 'transfers', 'reports', 'offline_sync',
                    'barcode', 'customers', 'suppliers', 'purchases',
                    'quotations', 'adjustments', 'multi_store',
                ],
                'status'                => true,
                'is_custom'             => false,
                'is_contact_sales'      => false,
                'is_featured'           => true,
                'sort_order'            => 2,
                'allowed_countries'     => null,
            ],

            // ── PROFESSIONAL ─────────────────────────────────────────────
            [
                'name'                  => 'Professional',
                'slug'                  => 'professional',
                'description'           => 'Full-featured ERP+POS for large operations. Up to 10 stores, 30 shops, 100 users. Includes accounting, FBR, CRM, HRM, and AI insights.',
                'price'                 => 149.00,
                'currency'              => 'USD',
                'price_pkr'             => 41720.00,
                'price_yearly'          => 1430.40,
                'price_yearly_pkr'      => 400512.00,
                'billing_cycle'         => 'monthly',
                'trial_days'            => 7,
                'max_stores'            => 10,
                'max_shops'             => 30,
                'max_registers'         => 60,
                'max_users'             => 100,
                'max_products'          => 100000,
                'per_shop_price'        => 10.00,
                'per_shop_price_pkr'    => 2800.00,
                'shop_type_pricing'     => self::SHOP_SURCHARGES_USD,
                'shop_type_pricing_pkr' => self::SHOP_SURCHARGES_PKR,
                'volume_pricing'        => self::VOLUME_USD,
                'volume_pricing_pkr'    => self::VOLUME_PKR,
                'features'              => [
                    'pos', 'stock', 'transfers', 'advanced_reports', 'offline_sync',
                    'barcode', 'customers', 'suppliers', 'purchases', 'quotations',
                    'adjustments', 'multi_store', 'accounting', 'fbr',
                    'crm', 'hrm', 'ai_reports', 'whatsapp', 'api_access',
                    'priority_support',
                ],
                'status'                => true,
                'is_custom'             => false,
                'is_contact_sales'      => false,
                'is_featured'           => false,
                'sort_order'            => 3,
                'allowed_countries'     => null,
            ],

            // ── PAKISTAN STARTER ────────────────────────────────────────
            [
                'name'                  => 'Starter (Pakistan)',
                'slug'                  => 'starter-pk',
                'description'           => 'Entry plan for Pakistani businesses. Rs.5,600/month. Includes FBR integration, 1 store, 3 shops.',
                'price'                 => 0.00,
                'currency'              => 'PKR',
                'price_pkr'             => 5600.00,
                'price_yearly'          => 0.00,
                'price_yearly_pkr'      => 53760.00,
                'billing_cycle'         => 'monthly',
                'trial_days'            => 14,
                'max_stores'            => 1,
                'max_shops'             => 3,
                'max_registers'         => 3,
                'max_users'             => 5,
                'max_products'          => 1000,
                'per_shop_price'        => 0.00,
                'per_shop_price_pkr'    => 2800.00,
                'shop_type_pricing'     => [],
                'shop_type_pricing_pkr' => self::SHOP_SURCHARGES_PKR,
                'volume_pricing'        => [],
                'volume_pricing_pkr'    => self::VOLUME_PKR,
                'features'              => ['pos', 'stock', 'basic_reports', 'barcode', 'customers', 'suppliers', 'fbr'],
                'status'                => true,
                'is_custom'             => false,
                'is_contact_sales'      => false,
                'is_featured'           => false,
                'sort_order'            => 4,
                'allowed_countries'     => ['PK'],
            ],

            // ── PAKISTAN GROWTH ──────────────────────────────────────────
            [
                'name'                  => 'Growth (Pakistan)',
                'slug'                  => 'growth-pk',
                'description'           => 'Multi-branch POS for Pakistani businesses. Rs.13,720/month. Includes FBR, offline sync, 3 stores, 10 shops.',
                'price'                 => 0.00,
                'currency'              => 'PKR',
                'price_pkr'             => 13720.00,
                'price_yearly'          => 0.00,
                'price_yearly_pkr'      => 131712.00,
                'billing_cycle'         => 'monthly',
                'trial_days'            => 14,
                'max_stores'            => 3,
                'max_shops'             => 10,
                'max_registers'         => 20,
                'max_users'             => 25,
                'max_products'          => 10000,
                'per_shop_price'        => 0.00,
                'per_shop_price_pkr'    => 2800.00,
                'shop_type_pricing'     => [],
                'shop_type_pricing_pkr' => self::SHOP_SURCHARGES_PKR,
                'volume_pricing'        => [],
                'volume_pricing_pkr'    => self::VOLUME_PKR,
                'features'              => [
                    'pos', 'stock', 'transfers', 'reports', 'offline_sync',
                    'barcode', 'customers', 'suppliers', 'purchases',
                    'quotations', 'adjustments', 'multi_store', 'fbr',
                ],
                'status'                => true,
                'is_custom'             => false,
                'is_contact_sales'      => false,
                'is_featured'           => true,
                'sort_order'            => 5,
                'allowed_countries'     => ['PK'],
            ],

            // ── ENTERPRISE (Contact Sales) ───────────────────────────────
            [
                'name'                  => 'Enterprise',
                'slug'                  => 'enterprise',
                'description'           => 'Tailored plan for large enterprises and chains. Custom store/shop limits, dedicated support, white-labeling, and SLA. Contact our sales team.',
                'price'                 => 0.00,
                'currency'              => 'USD',
                'price_pkr'             => 0.00,
                'price_yearly'          => 0.00,
                'price_yearly_pkr'      => 0.00,
                'billing_cycle'         => 'monthly',
                'trial_days'            => 0,
                'max_stores'            => 50,
                'max_shops'             => 200,
                'max_registers'         => 400,
                'max_users'             => 500,
                'max_products'          => 500000,
                'per_shop_price'        => 10.00,
                'per_shop_price_pkr'    => 2800.00,
                'shop_type_pricing'     => self::SHOP_SURCHARGES_USD,
                'shop_type_pricing_pkr' => self::SHOP_SURCHARGES_PKR,
                'volume_pricing'        => self::VOLUME_USD,
                'volume_pricing_pkr'    => self::VOLUME_PKR,
                'features'              => [
                    'pos', 'stock', 'transfers', 'advanced_reports', 'offline_sync',
                    'barcode', 'customers', 'suppliers', 'purchases', 'quotations',
                    'adjustments', 'multi_store', 'accounting', 'fbr',
                    'crm', 'hrm', 'ai_reports', 'whatsapp', 'api_access',
                    'white_label', 'dedicated_support', 'sla', 'custom_modules',
                ],
                'status'                => true,
                'is_custom'             => true,
                'is_contact_sales'      => true,
                'is_featured'           => false,
                'sort_order'            => 6,
                'allowed_countries'     => null,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
