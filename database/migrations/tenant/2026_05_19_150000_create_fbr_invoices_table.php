<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fbr_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('fbr_profile_id');
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('fbr_invoice_no')->nullable();
            $table->string('fbr_uuid')->nullable();
            $table->text('fbr_qr_payload')->nullable();
            $table->string('mode', 20)->default('sandbox'); // sandbox / production
            // queued, sending, synced, failed, retrying
            $table->string('status', 30)->default('queued');
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            // FK to central 'tenants' table removed: tenants lives in the central DB, not the per-tenant DB.
            $table->foreign('fbr_profile_id')->references('id')->on('fbr_profiles')->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
            $table->index(['sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fbr_invoices');
    }
};
