<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-Company (tenant) FBR Digital Invoice plan limits + overrides, set by the
 * super admin. NULL = unlimited. `plan_type` distinguishes Single Company from
 * Agent plans.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fbr_di_limits', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->unique();
            $table->string('plan_type', 20)->default('single'); // single | agent
            $table->unsignedBigInteger('monthly_invoice_limit')->nullable(); // null = unlimited
            $table->unsignedBigInteger('businesses_limit')->nullable();      // null = unlimited
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fbr_di_limits');
    }
};
