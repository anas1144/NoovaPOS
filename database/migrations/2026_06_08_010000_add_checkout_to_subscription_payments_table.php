<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds online-checkout support to subscription payments.
 *
 * pay_method        : 'request' (manual proof upload) | 'checkout' (online link
 *                     / local wallet-bank). Super admin enables/disables each.
 * checkout_channel  : the chosen channel key — e.g. 'link' (global hosted link),
 *                     or PK locals 'jazzcash','easypaisa','hbl','meezan','ubl'…
 * checkout_reference: tenant-entered txn id (for wallet/bank channels).
 * checkout_token    : opaque token for the hosted pay page URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_payments', 'pay_method')) {
                $table->string('pay_method', 20)->default('request')->after('method');
            }
            if (! Schema::hasColumn('subscription_payments', 'checkout_channel')) {
                $table->string('checkout_channel', 40)->nullable()->after('pay_method');
            }
            if (! Schema::hasColumn('subscription_payments', 'checkout_reference')) {
                $table->string('checkout_reference', 191)->nullable()->after('checkout_channel');
            }
            if (! Schema::hasColumn('subscription_payments', 'checkout_token')) {
                $table->string('checkout_token', 64)->nullable()->index()->after('checkout_reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            foreach (['pay_method', 'checkout_channel', 'checkout_reference', 'checkout_token'] as $col) {
                if (Schema::hasColumn('subscription_payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
