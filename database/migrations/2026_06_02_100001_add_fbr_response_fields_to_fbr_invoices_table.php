<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the three fields FBR POS API actually returns:
 *   InvoiceNumber → fbr_invoice_number  (the number to print on receipt)
 *   Code          → fbr_code            (0 = success)
 *   Response      → fbr_response        (human-readable status string)
 *
 * Also links sales back to their fbr_invoice row so the receipt can read
 * the FBR number directly off the sale record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fbr_invoices', function (Blueprint $table) {
            // FBR-assigned invoice number — this is what gets printed on receipt
            $table->string('fbr_invoice_number')->nullable()->after('fbr_uuid');
            // FBR response code (0 = accepted)
            $table->string('fbr_code', 20)->nullable()->after('fbr_invoice_number');
            // FBR plain-text response message
            $table->string('fbr_response', 255)->nullable()->after('fbr_code');
            // Service fee flag (FBR POS service fee Rs. 1 per transaction)
            $table->boolean('fbr_pos_service_fee')->default(false)->after('fbr_response');
        });

        // Link sales → their fbr_invoice so receipt can show FBR number
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('fbr_invoice_id')->nullable()->after('id');
            $table->foreign('fbr_invoice_id')
                  ->references('id')->on('fbr_invoices')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['fbr_invoice_id']);
            $table->dropColumn('fbr_invoice_id');
        });

        Schema::table('fbr_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'fbr_invoice_number',
                'fbr_code',
                'fbr_response',
                'fbr_pos_service_fee',
            ]);
        });
    }
};
