<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_order_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('table_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('waiter_id')->nullable();
            $table->string('ticket_no')->nullable();
            $table->string('order_type', 30)->default('dine_in'); // dine_in, takeaway, delivery
            // open, sent, in_progress, ready, served, cancelled
            $table->string('status', 30)->default('open');
            $table->text('note')->nullable();
            $table->timestamp('sent_to_kitchen_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamps();

            // FK to central 'tenants' table removed: tenants lives in the central DB, not the per-tenant DB.
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('set null');
            $table->foreign('table_id')->references('id')->on('restaurant_tables')->onDelete('set null');
            $table->index(['tenant_id', 'status', 'shop_id']);
        });

        Schema::create('kitchen_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->text('modifier')->nullable();
            // pending, cooking, ready, served, cancelled
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('kitchen_order_tickets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_order_items');
        Schema::dropIfExists('kitchen_order_tickets');
    }
};
