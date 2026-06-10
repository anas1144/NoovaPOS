<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cumulative add-on allowance per tenant — CENTRAL. Added on top of the plan's
 * limits when enforcing stores/users/products quotas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_addons', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->unique();
            $table->unsignedInteger('extra_shops')->default(0);
            $table->unsignedInteger('extra_users')->default(0);
            $table->unsignedInteger('extra_products')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_addons');
    }
};
