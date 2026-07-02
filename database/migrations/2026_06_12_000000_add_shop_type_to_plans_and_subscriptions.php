<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-shop-type plans: each plan targets one business type, a tenant may hold
 * several concurrent subscriptions (one per shop type), and each subscription
 * is tied to the store it provisioned.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'shop_type')) {
                $table->string('shop_type', 60)->nullable()->index()->after('slug');
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'shop_type')) {
                $table->string('shop_type', 60)->nullable()->index()->after('plan_id');
            }
            if (! Schema::hasColumn('subscriptions', 'store_id')) {
                $table->unsignedBigInteger('store_id')->nullable()->after('shop_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'shop_type')) {
                $table->dropColumn('shop_type');
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            foreach (['shop_type', 'store_id'] as $col) {
                if (Schema::hasColumn('subscriptions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
