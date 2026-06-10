<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_devices', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('store_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('device_uuid', 100);
            $table->string('name');
            $table->string('platform')->nullable();
            $table->string('app_version')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('shop_id')->references('id')->on('shops')->nullOnDelete()->cascadeOnUpdate();
            $table->unique(['tenant_id', 'device_uuid']);
            $table->index(['tenant_id', 'store_id', 'shop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_devices');
    }
};
