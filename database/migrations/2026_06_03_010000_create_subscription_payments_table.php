<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SaaS subscription payments — CENTRAL.
 *
 * Records each tenant's subscription payment / renewal request. Flow:
 *   1. Tenant picks a plan + billing cycle + number of periods on their Billing
 *      page -> a `pending` row is created (with an optional offline reference).
 *   2. Super admin confirms (marks `paid`) -> the tenant's Subscription is
 *      activated for period_start..period_end.
 *
 * `method`/`reference` allow offline checkout now and a payment gateway later
 * (a webhook would just call the same confirm logic).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('plan_id')->nullable();
            $table->string('billing_cycle', 20)->default('monthly'); // monthly | yearly
            $table->unsignedSmallInteger('periods')->default(1);     // e.g. 3 months / 2 years
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('USD');
            $table->string('status', 20)->default('pending');        // pending | paid | rejected
            $table->string('method', 30)->default('manual');         // manual | bank_transfer | gateway
            $table->string('reference')->nullable();                 // txn id / bank ref
            $table->text('note')->nullable();
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();  // tenant user id
            $table->unsignedBigInteger('confirmed_by')->nullable();  // super admin user id
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
