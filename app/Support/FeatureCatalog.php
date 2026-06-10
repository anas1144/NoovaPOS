<?php

namespace App\Support;

/**
 * Central catalog of toggleable features.
 *
 * A feature is effectively ON for a store only when it is enabled GLOBALLY
 * (super admin) AND enabled for that STORE (tenant admin). `shop_types` limits
 * a feature to certain store types (empty = all types).
 */
class FeatureCatalog
{
    // Restaurant
    public const KITCHEN          = 'kitchen';
    public const KITCHEN_DISPLAY  = 'kitchen_display';
    public const CUSTOMER_DISPLAY = 'customer_display';
    public const TABLE_SEATS      = 'table_seats';

    // Products / sales
    public const DEALS            = 'deals';
    public const UNIT_HIERARCHY   = 'unit_hierarchy';
    public const PRICE_TIERS      = 'price_tiers';

    // Order types
    public const TAKEAWAY         = 'takeaway';
    public const DELIVERY         = 'delivery';
    public const HOLD_BILL        = 'hold_bill';

    /**
     * @return array<int, array{key:string,label:string,group:string,shop_types:array}>
     */
    public static function all(): array
    {
        return [
            ['key' => self::KITCHEN,          'label' => 'Kitchen & KOT',        'group' => 'Restaurant', 'shop_types' => ['restaurant']],
            ['key' => self::KITCHEN_DISPLAY,  'label' => 'Kitchen Display',      'group' => 'Restaurant', 'shop_types' => ['restaurant']],
            ['key' => self::CUSTOMER_DISPLAY, 'label' => 'Customer Self-Display','group' => 'Restaurant', 'shop_types' => []],
            ['key' => self::TABLE_SEATS,      'label' => 'Table Seats',          'group' => 'Restaurant', 'shop_types' => ['restaurant']],

            ['key' => self::DEALS,            'label' => 'Deals / Combos',       'group' => 'Products', 'shop_types' => []],
            ['key' => self::UNIT_HIERARCHY,   'label' => 'Unit Hierarchy (box/pack/piece)', 'group' => 'Products', 'shop_types' => []],
            ['key' => self::PRICE_TIERS,      'label' => 'Multi-tier Pricing',   'group' => 'Products', 'shop_types' => []],

            ['key' => self::TAKEAWAY,         'label' => 'Takeaway Orders',      'group' => 'Orders', 'shop_types' => ['restaurant']],
            ['key' => self::DELIVERY,         'label' => 'Delivery Orders',      'group' => 'Orders', 'shop_types' => []],
            ['key' => self::HOLD_BILL,        'label' => 'Hold Bill',            'group' => 'Orders', 'shop_types' => []],
        ];
    }

    public static function keys(): array
    {
        return array_column(self::all(), 'key');
    }

    public static function isValid(string $key): bool
    {
        return in_array($key, self::keys(), true);
    }
}
