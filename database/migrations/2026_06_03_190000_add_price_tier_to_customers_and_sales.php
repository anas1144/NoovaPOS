<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customers get a default price tier; sales record the tier used.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'price_tier')) {
                $table->string('price_tier', 40)->default('retail')->after('name');
            }
        });

        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (! Schema::hasColumn('sales', 'price_tier')) {
                    $table->string('price_tier', 40)->nullable()->after('customer_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'price_tier')) {
            Schema::table('customers', fn (Blueprint $t) => $t->dropColumn('price_tier'));
        }
        if (Schema::hasColumn('sales', 'price_tier')) {
            Schema::table('sales', fn (Blueprint $t) => $t->dropColumn('price_tier'));
        }
    }
};
