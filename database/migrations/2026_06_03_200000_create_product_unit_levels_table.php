<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-product unit hierarchy (e.g. piece → pack(12) → bar(10 packs) → box(10
 * bars)). Each level stores how many BASE units (pieces) it equals, so any mix
 * of units on a sale can be converted to a single base quantity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_unit_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('name', 40);                 // piece, pack, bar, box…
            $table->decimal('factor_to_base', 16, 4)->default(1); // base units per 1 of this level
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_unit_levels');
    }
};
