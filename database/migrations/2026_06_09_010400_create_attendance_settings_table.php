<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant (optionally per-store) attendance configuration, stored as a single
 * JSON blob so toggles can evolve without migrations. Resolved with defaults by
 * AttendanceSettingService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('store_id')->nullable()->index();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
