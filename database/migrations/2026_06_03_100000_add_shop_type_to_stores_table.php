<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each store/branch carries one business type. Shops created under the store
 * inherit and lock to this type.
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
