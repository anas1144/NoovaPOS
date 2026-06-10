<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee-submitted attendance requests (corrections, missing check-out, leave,
 * etc.) that a manager approves or rejects. Approving a correction writes the
 * attendance record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->string('type', 30)->default('correction'); // correction | missing_checkout | leave | other
            $table->date('date');
            $table->dateTime('requested_check_in')->nullable();
            $table->dateTime('requested_check_out')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_requests');
    }
};
