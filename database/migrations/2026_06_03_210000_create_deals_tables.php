<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deals / combos: a named bundle of products sold at a fixed deal price.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('store_id')->nullable()->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('deal_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deal_id')->index();
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_items');
        Schema::dropIfExists('deals');
    }
};
