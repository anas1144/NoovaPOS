<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer self-service displays (kiosks) and the orders they create.
 *
 * A display is mounted at a store/shop and attached to a kitchen. A customer
 * orders on it (no login); the order routes a KOT to that kitchen and lands on
 * the shop manager's board.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_displays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('kitchen_id')->nullable();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('display_id')->nullable();
            $table->unsignedBigInteger('kitchen_id')->nullable();
            $table->unsignedBigInteger('table_id')->nullable();
            $table->unsignedBigInteger('kot_id')->nullable();
            $table->unsignedBigInteger('waiter_id')->nullable();
            $table->string('token_no', 20);
            $table->string('customer_name')->nullable();
            $table->json('seats')->nullable();
            $table->json('items');               // [{product_id|deal_id, name, quantity, price}]
            $table->decimal('total', 15, 2)->default(0);
            $table->string('status', 20)->default('new'); // new|accepted|assigned|served|cancelled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
        Schema::dropIfExists('customer_displays');
    }
};
