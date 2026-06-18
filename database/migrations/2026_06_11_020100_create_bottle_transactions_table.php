<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable-bottle ledger for Water Supply: bottles issued to / returned by a
 * customer. The running balance = sum(issue) - sum(return).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bottle_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('delivery_route_id')->nullable()->index();
            $table->string('type', 10); // issue | return
            $table->decimal('quantity', 12, 2)->default(0);
            $table->date('date');
            $table->string('note', 191)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bottle_transactions');
    }
};
