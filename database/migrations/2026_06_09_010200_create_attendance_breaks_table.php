<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A break period within an attendance day (start break / end break).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_breaks', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('attendance_id')->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->unsignedBigInteger('duration_seconds')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_breaks');
    }
};
