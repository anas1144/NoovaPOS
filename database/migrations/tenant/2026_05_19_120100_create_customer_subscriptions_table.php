<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('recurring_plan_id');
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->decimal('default_quantity', 12, 3)->default(1);
            $table->json('schedule_days')->nullable(); // [1=mon..7=sun]
            $table->string('route_name')->nullable();
            $table->string('delivery_address')->nullable();
            $table->date('start_date')->nullable();
            $table->date('next_invoice_date')->nullable();
            // active, paused, ended
            $table->string('status', 30)->default('active');
            $table->decimal('deposit_paid', 14, 2)->default(0);
            $table->decimal('bottles_with_customer', 10, 0)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('recurring_plan_id')->references('id')->on('recurring_plans')->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
            $table->index(['route_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_subscriptions');
    }
};
