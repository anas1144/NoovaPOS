<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plan-level separate-database option: whether the plan offers an isolated
 * per-tenant database, the extra price for it, and the user allowance that
 * applies when a tenant is on a separate database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'offers_separate_db')) {
                $table->boolean('offers_separate_db')->default(false)->after('max_products');
                $table->decimal('separate_db_price', 12, 2)->default(0)->after('offers_separate_db');
                $table->unsignedInteger('separate_db_max_users')->nullable()->after('separate_db_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            foreach (['offers_separate_db', 'separate_db_price', 'separate_db_max_users'] as $col) {
                if (Schema::hasColumn('plans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
