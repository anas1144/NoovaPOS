<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Currency support — default USD, PKR for Pakistan
            $table->string('currency', 10)->default('USD')->after('price');
            $table->decimal('price_pkr', 12, 2)->default(0)->after('currency')
                ->comment('Price in PKR for Pakistani tenants');

            // Per-shop pricing engine
            $table->decimal('per_shop_price', 10, 2)->default(10.00)->after('price_pkr')
                ->comment('Base price per shop in USD');
            $table->decimal('per_shop_price_pkr', 10, 2)->default(2800.00)->after('per_shop_price')
                ->comment('Base price per shop in PKR');

            // Shop-type surcharge config (JSON)
            // e.g. {"restaurant": 5, "pharmacy": 3, "water_supply": 4}
            $table->json('shop_type_pricing')->nullable()->after('per_shop_price_pkr')
                ->comment('Extra USD charge per shop type on top of per_shop_price');
            $table->json('shop_type_pricing_pkr')->nullable()->after('shop_type_pricing')
                ->comment('Extra PKR charge per shop type');

            // Pricing discount tiers for additional shops
            // e.g. {"2": 5, "3": 4, "5": 3} — USD price per shop when count reaches tier
            $table->json('volume_pricing')->nullable()->after('shop_type_pricing_pkr')
                ->comment('Volume discount: shop count → per-shop price in USD');
            $table->json('volume_pricing_pkr')->nullable()->after('volume_pricing');

            // Custom / Contact Sales plan
            $table->boolean('is_custom')->default(false)->after('status')
                ->comment('If true this is a custom plan built by super admin');
            $table->boolean('is_contact_sales')->default(false)->after('is_custom')
                ->comment('If true show Contact Sales CTA instead of Subscribe button');

            // Yearly billing option
            $table->decimal('price_yearly', 12, 2)->default(0)->after('price_pkr');
            $table->decimal('price_yearly_pkr', 12, 2)->default(0)->after('price_yearly');

            // Sorting for plan cards
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_contact_sales');

            // Highlighted / recommended plan
            $table->boolean('is_featured')->default(false)->after('sort_order');

            // Country restrictions (null = all countries)
            $table->json('allowed_countries')->nullable()->after('is_featured')
                ->comment('null = available everywhere; ["PK"] = Pakistan only');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'currency', 'price_pkr', 'per_shop_price', 'per_shop_price_pkr',
                'shop_type_pricing', 'shop_type_pricing_pkr',
                'volume_pricing', 'volume_pricing_pkr',
                'is_custom', 'is_contact_sales',
                'price_yearly', 'price_yearly_pkr',
                'sort_order', 'is_featured', 'allowed_countries',
            ]);
        });
    }
};
