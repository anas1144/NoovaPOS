<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FBR Digital Invoices. Created as Draft locally; synced to FBR on demand by a
 * user with the fbr_invoice_sync permission. The FBR invoice number + QR are
 * assigned on acceptance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fbr_di_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('fbr_business_id')->index();
            $table->string('local_no', 40)->nullable();         // internal draft number
            $table->string('fbr_invoice_no', 60)->nullable();   // assigned by FBR after sync
            $table->string('ref_inv_no', 80)->nullable();
            $table->string('invoice_type', 20)->default('sale'); // sale | credit_note | debit_note
            $table->date('invoice_date');
            // Buyer
            $table->string('buyer_ntn_cnic', 20)->nullable();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_registration_type', 20)->default('unregistered'); // registered | unregistered
            $table->string('buyer_province', 60)->nullable();
            $table->string('buyer_address')->nullable();
            // Totals
            $table->decimal('value_excl_tax', 16, 2)->default(0);
            $table->decimal('sales_tax', 16, 2)->default(0);
            $table->decimal('further_tax', 16, 2)->default(0);
            $table->decimal('total_incl_tax', 16, 2)->default(0);
            // Lifecycle
            $table->string('status', 20)->default('draft'); // draft|pending_sync|syncing|synced|accepted|rejected|cancelled
            $table->text('qr_payload')->nullable();
            $table->longText('sync_response')->nullable();
            $table->text('sync_error')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fbr_di_invoices');
    }
};
