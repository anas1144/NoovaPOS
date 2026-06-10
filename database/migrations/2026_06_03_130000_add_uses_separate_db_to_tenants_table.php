<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant flag controlling whether this tenant runs on its own isolated
 * database. The runtime DB switch only switches connections for tenants where
 * this is true (in addition to the global TENANCY_DB_SWITCH master flag).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'uses_separate_db')) {
                $table->boolean('uses_separate_db')->default(false)->after('store_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'uses_separate_db')) {
                $table->dropColumn('uses_separate_db');
            }
        });
    }
};
