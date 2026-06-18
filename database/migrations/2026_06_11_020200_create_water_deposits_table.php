<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer bottle deposits (money held against reusable bottles), refundable on
 * return.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('water_deposits', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->decimal('amount', 16, 2)->default(0);
            $table->decimal('bottles', 12, 2)->default(0);
            $table->string('status', 12)->default('held'); // held | refunded
            $table->date('date');
            $table->dateTime('refunded_at')->nullable();
            $table->string('note', 191)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_deposits');
    }
};
