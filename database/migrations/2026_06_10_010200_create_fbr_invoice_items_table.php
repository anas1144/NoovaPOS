<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Line items for an FBR Digital Invoice (HS code, qty, sales-tax breakdown).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fbr_di_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('fbr_di_invoice_id')->index();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedInteger('sr_no')->default(1);
            $table->string('hs_code', 20)->nullable();
            $table->string('description');
            $table->string('uom', 30)->nullable();
            $table->decimal('rate_per_unit', 16, 2)->default(0);
            $table->decimal('quantity', 16, 2)->default(0);
            $table->decimal('value_excl_tax', 16, 2)->default(0);
            $table->decimal('rate_of_sales_tax', 8, 2)->default(0); // percent
            $table->decimal('value_of_sales_tax', 16, 2)->default(0);
            $table->decimal('value_incl_tax', 16, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fbr_di_invoice_items');
    }
};
