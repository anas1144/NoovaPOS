<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            if (! Schema::hasColumn('shops', 'shop_type')) {
                $table->string('shop_type', 60)->default('retail')->after('code');
            }
            if (! Schema::hasColumn('shops', 'enabled_modules')) {
                $table->json('enabled_modules')->nullable()->after('shop_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            if (Schema::hasColumn('shops', 'enabled_modules')) {
                $table->dropColumn('enabled_modules');
            }
            if (Schema::hasColumn('shops', 'shop_type')) {
                $table->dropColumn('shop_type');
            }
        });
    }
};
