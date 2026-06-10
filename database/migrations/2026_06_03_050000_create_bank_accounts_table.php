<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform payout bank accounts — CENTRAL, managed by the super admin.
 *
 * Tenants pay their subscription into the account matching their country, so
 * each account is tagged with a country ISO code. Shown on the tenant Billing
 * page filtered by the tenant's country.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('country', 5)->index();   // ISO short_code: PK, US, …
            $table->string('bank_name');
            $table->string('account_title');
            $table->string('account_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('swift')->nullable();
            $table->string('currency', 8)->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
