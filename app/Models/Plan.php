<?php

namespace App\Models;

use App\Traits\CentralConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends BaseModel
{
    use HasFactory, CentralConnection;

    protected $table = 'plans';

    // Shop types supported by NoovaPOS
    const SHOP_TYPES = [
        'retail'            => 'Retail POS',
        'restaurant'        => 'Restaurant POS',
        'pharmacy'          => 'Pharmacy POS',
        'water_supply'      => 'Water Supply POS',
        'bakery'            => 'Bakery POS',
        'electronics'       => 'Electronics POS',
        'fashion'           => 'Fashion Store POS',
        'distribution'      => 'Distribution POS',
        'monthly_service'   => 'Monthly Service POS',
        'fbr_digital'       => 'FBR Digital Invoice',
        'custom'            => 'Custom Business Type',
    ];

    // Modules enabled per shop type
    const SHOP_TYPE_MODULES = [
        'retail' => [
            'pos', 'inventory', 'barcode', 'grn', 'stock_transfer',
            'supplier_management', 'purchases', 'sales', 'reports',
        ],
        'restaurant' => [
            'pos', 'tables', 'halls', 'kot', 'kitchen_display',
            'waiter_app', 'split_bills', 'dine_in', 'takeaway', 'delivery',
        ],
        'pharmacy' => [
            'pos', 'inventory', 'expiry_tracking', 'batch_tracking',
            'barcode', 'purchases', 'sales', 'reports',
        ],
        'water_supply' => [
            'pos', 'recurring_delivery', 'bottle_tracking', 'route_management',
            'delivery_scheduling', 'deposit_tracking', 'recurring_invoices',
        ],
        'bakery' => [
            'pos', 'inventory', 'production', 'sales', 'purchases', 'reports',
        ],
        'electronics' => [
            'pos', 'inventory', 'serial_numbers', 'warranty_tracking',
            'barcode', 'purchases', 'sales', 'reports',
        ],
        'fashion' => [
            'pos', 'inventory', 'variations', 'barcode', 'purchases', 'sales', 'reports',
        ],
        'distribution' => [
            'pos', 'inventory', 'stock_transfer', 'route_management',
            'purchases', 'sales', 'reports', 'delivery_scheduling',
        ],
        'monthly_service' => [
            'pos', 'recurring_invoices', 'subscriptions', 'billing_cycles',
            'overdue_tracking', 'customers',
        ],
        'custom' => [
            'pos', 'inventory', 'sales', 'purchases', 'reports',
        ],
        // FBR Digital Invoice: NO pos/billing — dedicated FBR invoicing only.
        'fbr_digital' => [
            'fbr_invoices', 'fbr_sync', 'fbr_reports', 'fbr_errors',
            'fbr_sandbox', 'customers', 'products', 'grn', 'rtc',
        ],
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'price_pkr',
        'price_yearly',
        'price_yearly_pkr',
        'billing_cycle',
        'trial_days',
        'max_stores',
        'max_shops',
        'max_registers',
        'max_users',
        'max_products',
        'offers_separate_db',
        'separate_db_price',
        'separate_db_max_users',
        'per_shop_price',
        'per_shop_price_pkr',
        'shop_type_pricing',
        'shop_type_pricing_pkr',
        'volume_pricing',
        'volume_pricing_pkr',
        'features',
        'status',
        'is_custom',
        'is_contact_sales',
        'is_featured',
        'sort_order',
        'allowed_countries',
    ];

    protected $casts = [
        'price'                 => 'decimal:2',
        'price_pkr'             => 'decimal:2',
        'price_yearly'          => 'decimal:2',
        'price_yearly_pkr'      => 'decimal:2',
        'per_shop_price'        => 'decimal:2',
        'per_shop_price_pkr'    => 'decimal:2',
        'features'              => 'array',
        'shop_type_pricing'     => 'array',
        'shop_type_pricing_pkr' => 'array',
        'volume_pricing'        => 'array',
        'volume_pricing_pkr'    => 'array',
        'allowed_countries'     => 'array',
        'status'                => 'boolean',
        'offers_separate_db'    => 'boolean',
        'separate_db_price'     => 'decimal:2',
        'is_custom'             => 'boolean',
        'is_contact_sales'      => 'boolean',
        'is_featured'           => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Calculate dynamic price for a given number of shops in a currency.
     * Applies volume discount tiers if configured.
     */
    public function calculateShopPrice(int $shopCount, string $currency = 'USD'): float
    {
        $basePrice   = $currency === 'PKR' ? (float) $this->per_shop_price_pkr : (float) $this->per_shop_price;
        $volumeTiers = $currency === 'PKR' ? ($this->volume_pricing_pkr ?? []) : ($this->volume_pricing ?? []);

        if (empty($volumeTiers)) {
            return $basePrice * $shopCount;
        }

        // Sort tiers descending so we match the highest applicable tier first
        krsort($volumeTiers);
        $pricePerShop = $basePrice;
        foreach ($volumeTiers as $minCount => $tierPrice) {
            if ($shopCount >= (int) $minCount) {
                $pricePerShop = (float) $tierPrice;
                break;
            }
        }

        return $pricePerShop * $shopCount;
    }
}
