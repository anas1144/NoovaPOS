<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A task an employee works on during an attendance day (office / personal).
 * Supports start / pause / resume / complete with accumulated duration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('attendance_id')->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->string('name', 191);
            $table->string('category', 20)->default('office'); // office | personal
            $table->string('status', 20)->default('running');  // running | paused | completed
            $table->dateTime('started_at')->nullable();
            $table->dateTime('resumed_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->unsignedBigInteger('duration_seconds')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_tasks');
    }
};
