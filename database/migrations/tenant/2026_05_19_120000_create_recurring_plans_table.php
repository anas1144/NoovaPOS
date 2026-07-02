<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('name');
            $table->string('billing_cycle', 30)->default('monthly');
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('deposit_amount', 14, 2)->default(0);
            $table->boolean('is_bottle')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();

            // FK to central 'tenants' table removed: tenants lives in the central DB, not the per-tenant DB.
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_plans');
    }
};
