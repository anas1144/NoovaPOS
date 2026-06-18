<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks when an overdue-payment reminder was last sent for a recurring invoice
 * (Monthly Service overdue tracking).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('recurring_invoices', 'reminded_at')) {
                $table->dateTime('reminded_at')->nullable()->after('due_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recurring_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('recurring_invoices', 'reminded_at')) {
                $table->dropColumn('reminded_at');
            }
        });
    }
};
