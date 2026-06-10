<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant country (ISO code, e.g. PK, US). Set at tenant registration and editable
 * in the user profile. Drives country-based plan and bank-account visibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'country')) {
                $table->string('country', 5)->nullable()->after('active_store_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'country')) {
                $table->dropColumn('country');
            }
        });
    }
};
