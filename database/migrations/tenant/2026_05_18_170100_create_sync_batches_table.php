<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_batches', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('offline_device_id');
            $table->string('batch_uuid', 100);
            $table->string('status', 30)->default('queued');
            $table->unsignedInteger('items_count')->default(0);
            $table->unsignedInteger('synced_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // FK to central 'tenants' table removed: tenants lives in the central DB, not the per-tenant DB.
            $table->foreign('offline_device_id')->references('id')->on('offline_devices')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unique(['tenant_id', 'batch_uuid']);
            $table->index(['tenant_id', 'offline_device_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_batches');
    }
};
