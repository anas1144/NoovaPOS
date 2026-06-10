<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kitchen links:
 *  - restaurant_halls.kitchen_id  → hall's default kitchen
 *  - users.kitchen_id             → a waiter's (or kitchen user's) kitchen
 *  - kitchen_order_tickets.kitchen_id → which kitchen a KOT was routed to
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restaurant_halls')) {
            Schema::table('restaurant_halls', function (Blueprint $table) {
                if (! Schema::hasColumn('restaurant_halls', 'kitchen_id')) {
                    $table->unsignedBigInteger('kitchen_id')->nullable()->after('store_id');
                }
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'kitchen_id')) {
                $table->unsignedBigInteger('kitchen_id')->nullable()->after('active_store_id');
            }
        });

        if (Schema::hasTable('kitchen_order_tickets')) {
            Schema::table('kitchen_order_tickets', function (Blueprint $table) {
                if (! Schema::hasColumn('kitchen_order_tickets', 'kitchen_id')) {
                    $table->unsignedBigInteger('kitchen_id')->nullable()->after('shop_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('restaurant_halls', 'kitchen_id')) {
            Schema::table('restaurant_halls', fn (Blueprint $t) => $t->dropColumn('kitchen_id'));
        }
        if (Schema::hasColumn('users', 'kitchen_id')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('kitchen_id'));
        }
        if (Schema::hasColumn('kitchen_order_tickets', 'kitchen_id')) {
            Schema::table('kitchen_order_tickets', fn (Blueprint $t) => $t->dropColumn('kitchen_id'));
        }
    }
};
