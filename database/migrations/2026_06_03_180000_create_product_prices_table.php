<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-product price for a non-default tier. The default ("retail") tier uses the
 * product's base product_price; tiers like wholesale/m20 are stored here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('price_tier', 40);
            $table->decimal('price', 15, 4)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'price_tier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
