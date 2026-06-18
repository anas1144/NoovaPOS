<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Warranty registrations (Electronics) — by product + serial, with a period and
 * computed expiry for lookup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranties', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('serial_no', 120)->index();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->date('start_date');
            $table->unsignedInteger('months')->default(12);
            $table->date('end_date');
            $table->string('status', 16)->default('active'); // active | expired | void
            $table->string('note', 191)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranties');
    }
};
