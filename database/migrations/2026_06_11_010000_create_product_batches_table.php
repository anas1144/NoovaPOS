<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch-wise stock with expiry (Pharmacy / Bakery / any perishable shop type).
 * Each row is a batch of a product with its own expiry date and on-hand qty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('store_id')->nullable()->index();
            $table->string('batch_no', 80);
            $table->date('mfg_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->decimal('quantity', 16, 2)->default(0);
            $table->decimal('cost', 16, 2)->default(0);
            $table->string('status', 16)->default('active'); // active | quarantined | expired
            $table->timestamps();

            $table->index(['product_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
    }
};
