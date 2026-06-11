<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FBR Digital Invoice "Business" — the seller entity (maps to a Store).
 * A Company (= Tenant) can own several Businesses, each with its own NTN/STRN
 * and FBR credentials.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fbr_businesses', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('store_id')->nullable()->index();
            $table->string('name');
            $table->string('ntn_cnic', 20)->nullable();
            $table->string('strn', 30)->nullable();
            $table->string('province', 60)->nullable();
            $table->string('address')->nullable();
            $table->string('business_activity', 120)->nullable();
            $table->string('pos_id', 60)->nullable();
            $table->string('environment', 12)->default('sandbox'); // sandbox | production
            $table->text('sandbox_token')->nullable();
            $table->text('production_token')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fbr_businesses');
    }
};
