<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distribution van load-out: a load of goods dispatched to a route/driver for a
 * day, reconciled afterward (delivered vs returned).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_loads', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('delivery_route_id')->nullable()->index();
            $table->unsignedBigInteger('driver_user_id')->nullable()->index();
            $table->date('date');
            $table->string('vehicle', 60)->nullable();
            $table->string('status', 16)->default('loaded'); // loaded | out | reconciled
            $table->string('note', 191)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('dispatch_load_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('dispatch_load_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->decimal('loaded_qty', 16, 2)->default(0);
            $table->decimal('delivered_qty', 16, 2)->default(0);
            $table->decimal('returned_qty', 16, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_load_items');
        Schema::dropIfExists('dispatch_loads');
    }
};
