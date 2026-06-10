<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee attendance code — the value encoded in the printed Barcode / QR card
 * and matched on scan. Auto-generated on first enrolment. Lives on `employees`
 * (the HR module's people table), alongside the existing `employee_code`.
 *
 * (Filename kept for migration-order stability; targets `employees`.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'attendance_code')) {
                $table->string('attendance_code', 64)->nullable()->after('employee_code')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'attendance_code')) {
                $table->dropColumn('attendance_code');
            }
        });
    }
};
