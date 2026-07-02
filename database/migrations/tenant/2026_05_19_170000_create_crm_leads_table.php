<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('source', 60)->nullable(); // walk_in, website, referral, ad, demo_request, manual
            // new, contacted, qualified, proposal, won, lost
            $table->string('stage', 30)->default('new');
            $table->decimal('estimated_value', 14, 2)->default(0);
            $table->unsignedInteger('probability')->default(0);
            $table->date('expected_close_date')->nullable();
            $table->date('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // FK to central 'tenants' table removed: tenants lives in the central DB, not the per-tenant DB.
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->index(['tenant_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_leads');
    }
};
