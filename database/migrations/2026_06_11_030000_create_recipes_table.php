<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Production recipes (Bakery): a finished product made from a set of raw
 * ingredients, with an expected yield per run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();   // finished product
            $table->string('name', 150);
            $table->decimal('yield_qty', 16, 2)->default(1);     // finished units per run
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('recipe_id')->index();
            $table->unsignedBigInteger('product_id')->index();   // raw ingredient
            $table->decimal('quantity', 16, 4)->default(0);      // per single yield unit
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_items');
        Schema::dropIfExists('recipes');
    }
};
