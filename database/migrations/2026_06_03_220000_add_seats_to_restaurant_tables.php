<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Number of seats per restaurant table (used by the customer kiosk for
 * table + seat selection).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restaurant_tables')) {
            Schema::table('restaurant_tables', function (Blueprint $table) {
                if (! Schema::hasColumn('restaurant_tables', 'seats')) {
                    $table->unsignedInteger('seats')->default(4)->after('capacity');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('restaurant_tables', 'seats')) {
            Schema::table('restaurant_tables', fn (Blueprint $t) => $t->dropColumn('seats'));
        }
    }
};
