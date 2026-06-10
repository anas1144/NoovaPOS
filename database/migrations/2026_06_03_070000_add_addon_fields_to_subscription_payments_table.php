<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a subscription_payment also represent an add-on purchase (extra shops /
 * users / products) instead of a plan subscription.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_payments', 'type')) {
                $table->string('type', 20)->default('subscription')->after('id'); // subscription | addon
            }
            if (! Schema::hasColumn('subscription_payments', 'addon_shops')) {
                $table->unsignedInteger('addon_shops')->default(0)->after('periods');
                $table->unsignedInteger('addon_users')->default(0)->after('addon_shops');
                $table->unsignedInteger('addon_products')->default(0)->after('addon_users');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            foreach (['type', 'addon_shops', 'addon_users', 'addon_products'] as $col) {
                if (Schema::hasColumn('subscription_payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
