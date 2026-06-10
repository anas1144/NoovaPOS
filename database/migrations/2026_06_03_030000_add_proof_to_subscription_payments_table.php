<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment proof (screenshot / receipt) uploaded by the tenant when requesting a
 * subscription. Stored on the public disk; only the relative path is kept here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_payments', 'proof_path')) {
                $table->string('proof_path')->nullable()->after('reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_payments', 'proof_path')) {
                $table->dropColumn('proof_path');
            }
        });
    }
};
