<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks which store/branch a user currently has selected.
 *
 * Previously "active store" was derived from the user's tenant_id, but every
 * store in a tenant shares the same tenant_id, so the selection could never
 * change. This column records the actual selected store per user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'active_store_id')) {
                $table->unsignedBigInteger('active_store_id')->nullable()->after('tenant_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'active_store_id')) {
                $table->dropColumn('active_store_id');
            }
        });
    }
};
