<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds Pakistan-specific FBR POS fields directly to the shops table.
 *
 * When a shop owner selects country = "PK" during shop setup, the UI shows
 * optional FBR fields. These are stored per-shop because each POS counter
 * in Pakistan has its own POSID and auth token issued by the licensed
 * integrator (via FBR DI / PRAL portal).
 *
 * fbr_req_type:
 *   0 = Live via esp.fbr.gov.pk:8244
 *   1 = Live via gw.fbr.gov.pk
 *   2 = Sandbox / FBR disabled (default)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // Country selection (ISO 3166-1 alpha-2, e.g. "PK", "US")
            $table->string('country', 5)->nullable()->after('code');

            // FBR POS fields — only required when country = PK
            $table->string('fbr_pos_id', 120)->nullable()->after('country');
            $table->text('fbr_auth_token')->nullable()->after('fbr_pos_id');
            $table->string('fbr_ntn', 32)->nullable()->after('fbr_auth_token');
            $table->string('fbr_strn', 32)->nullable()->after('fbr_ntn');

            // 0=live-esp  1=live-gw  2=sandbox(disabled)
            $table->tinyInteger('fbr_req_type')->default(2)->after('fbr_strn');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn([
                'country',
                'fbr_pos_id',
                'fbr_auth_token',
                'fbr_ntn',
                'fbr_strn',
                'fbr_req_type',
            ]);
        });
    }
};
