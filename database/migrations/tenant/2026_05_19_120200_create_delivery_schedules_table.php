<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable();
            $table->unsignedBigInteger('customer_subscription_id');
            $table->unsignedBigInteger('customer_id');
            $table->date('scheduled_date');
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('extra_quantity', 12, 3)->default(0);
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('route_name')->nullable();
            // scheduled, skipped, delivered, partial, cancelled
            $table->string('status', 30)->default('scheduled');
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('bottles_returned', 10, 0)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('customer_subscription_id')->references('id')->on('customer_subscriptions')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->index(['tenant_id', 'scheduled_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_schedules');
    }
};
