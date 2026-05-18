<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_queue', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('offline_device_id');
            $table->unsignedBigInteger('sync_batch_id')->nullable();
            $table->string('local_uuid', 100);
            $table->string('entity_type', 60);
            $table->string('operation', 30)->default('create');
            $table->json('payload');
            $table->string('status', 30)->default('queued');
            $table->text('error_message')->nullable();
            $table->string('server_reference_type')->nullable();
            $table->unsignedBigInteger('server_reference_id')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('offline_device_id')->references('id')->on('offline_devices')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('sync_batch_id')->references('id')->on('sync_batches')->nullOnDelete()->cascadeOnUpdate();
            $table->unique(['tenant_id', 'offline_device_id', 'local_uuid']);
            $table->index(['tenant_id', 'entity_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_queue');
    }
};
