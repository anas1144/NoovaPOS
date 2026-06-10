<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Price tiers per tenant (retail, wholesale, m20, …). "retail" is the default
 * and maps to the product's base price; other tiers store per-product overrides
 * in product_prices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('key', 40);
            $table->string('label');
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_tiers');
    }
};
