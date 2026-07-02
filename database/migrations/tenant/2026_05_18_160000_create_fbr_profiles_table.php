<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fbr_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('business_name');
            $table->string('ntn', 32);
            $table->string('strn', 32)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('business_activity', 255)->nullable();
            $table->string('pos_id', 120)->nullable();
            $table->text('sandbox_token')->nullable();
            $table->text('production_token')->nullable();
            $table->string('mode', 20)->default('sandbox');
            $table->boolean('enabled')->default(false);
            $table->timestamps();

            // FK to central 'tenants' table removed: tenants lives in the central DB, not the per-tenant DB.
            $table->foreign('store_id')->references('id')->on('stores')->onUpdate('cascade')->onDelete('set null');
            $table->index(['tenant_id', 'store_id']);
            $table->unique(['tenant_id', 'store_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fbr_profiles');
    }
};

