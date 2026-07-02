<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant copy: each store/branch carries one business type. Shops created
 * under the store inherit and lock to this type. (Mirror of the central
 * add_shop_type_to_stores_table migration so separate-DB tenants get the
 * column too.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'shop_type')) {
                $table->string('shop_type', 60)->default('retail')->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'shop_type')) {
                $table->dropColumn('shop_type');
            }
        });
    }
};
