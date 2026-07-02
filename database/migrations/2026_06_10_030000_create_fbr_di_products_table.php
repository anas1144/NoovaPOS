<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A dedicated product/item catalog for FBR Digital Invoicing. Separate from the
 * POS products table — DI businesses maintain their own items (HS code, UoM,
 * rate, sales-tax %, SRO details) and pick them when building invoices.
 * Central table, scoped per tenant via tenant_id (like fbr_businesses).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fbr_di_products', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('fbr_business_id')->nullable()->index();
            $table->string('name');
            $table->string('hs_code', 20)->nullable()->index();
            $table->string('uom', 30)->nullable();
            $table->decimal('rate_per_unit', 16, 2)->default(0);
            $table->decimal('rate_of_sales_tax', 8, 2)->default(0); // percent
            $table->string('sro_no', 50)->nullable();
            $table->string('sro_item_serial', 50)->nullable();
            $table->string('sale_type', 100)->nullable();
            $table->string('category', 100)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fbr_di_products');
    }
};
