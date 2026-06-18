<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Delivery routes (Water Supply / Distribution) — a named area assigned to a
 * driver, with customers grouped onto it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_routes', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('store_id')->nullable()->index();
            $table->string('name', 120);
            $table->string('area', 191)->nullable();
            $table->unsignedBigInteger('driver_user_id')->nullable()->index();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // Customers belong to a route (nullable FK column added on customers).
        if (Schema::hasTable('customers') && ! Schema::hasColumn('customers', 'delivery_route_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unsignedBigInteger('delivery_route_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'delivery_route_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('delivery_route_id');
            });
        }
        Schema::dropIfExists('delivery_routes');
    }
};
