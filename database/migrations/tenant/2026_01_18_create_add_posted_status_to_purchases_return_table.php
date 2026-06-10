<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchases_return', function (Blueprint $table) {
            $table->tinyInteger('posted_status')->default(0)->comment('0: Draft, 1: Posted')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases_return', function (Blueprint $table) {
            $table->dropColumn('posted_status');
        });
    }
};
