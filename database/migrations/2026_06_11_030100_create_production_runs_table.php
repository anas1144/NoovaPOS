<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A production run: producing N batches of a recipe, recording finished output,
 * wastage, and the ingredients consumed (snapshot).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('recipe_id')->index();
            $table->date('date');
            $table->decimal('batches', 16, 2)->default(1);       // how many recipe-yields
            $table->decimal('produced_qty', 16, 2)->default(0);  // finished units
            $table->decimal('wastage_qty', 16, 2)->default(0);
            $table->json('consumed')->nullable();                // [{product_id, quantity}]
            $table->string('status', 16)->default('completed');  // planned | completed
            $table->string('note', 191)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_runs');
    }
};
