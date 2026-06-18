<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Individual serial-numbered units of a product (Electronics).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_serials', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('store_id')->nullable()->index();
            $table->string('serial_no', 120);
            $table->string('status', 16)->default('in_stock'); // in_stock | sold | returned | faulty
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->dateTime('sold_at')->nullable();
            $table->string('note', 191)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'serial_no']);
            $table->index('serial_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_serials');
    }
};
