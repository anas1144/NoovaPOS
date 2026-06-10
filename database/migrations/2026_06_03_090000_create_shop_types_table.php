<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registry of business/shop types — CENTRAL, super-admin controlled.
 * The super admin enables/disables which types tenants may pick for a store.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_types', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();   // retail, restaurant, water_supply, …
            $table->string('label');
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_types');
    }
};
