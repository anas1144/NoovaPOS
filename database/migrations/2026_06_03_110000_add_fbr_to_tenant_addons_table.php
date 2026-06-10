<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FBR (Pakistan) activation state per tenant. FBR is a paid, time-bound add-on
 * available only to tenants whose country is PK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_addons', function (Blueprint $table) {
            if (! Schema::hasColumn('tenant_addons', 'fbr_enabled')) {
                $table->boolean('fbr_enabled')->default(false)->after('extra_products');
                $table->timestamp('fbr_expires_at')->nullable()->after('fbr_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenant_addons', function (Blueprint $table) {
            foreach (['fbr_enabled', 'fbr_expires_at'] as $col) {
                if (Schema::hasColumn('tenant_addons', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
