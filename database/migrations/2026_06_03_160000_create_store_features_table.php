<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-store feature toggles (tenant admin controlled). A feature is effectively
 * ON only when it is enabled globally (super admin) AND for the store here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_features', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('store_id')->index();
            $table->string('feature_key', 60);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'feature_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_features');
    }
};
