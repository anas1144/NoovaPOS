<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the existing HR `attendances` table (employee_id / date / check_in_at /
 * check_out_at / hours_worked / status / note) with the fields the POS-integrated
 * attendance kiosk needs: capture method, work category, accumulated seconds,
 * break flag, and the admin who entered a manual record.
 *
 * (Filename kept for migration-order stability; this is an ALTER, not a CREATE.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $add = function (string $col, callable $def) use ($table) {
                if (! Schema::hasColumn('attendances', $col)) {
                    $def($table);
                }
            };

            $add('store_id', fn ($t) => $t->unsignedBigInteger('store_id')->nullable()->after('employee_id'));
            $add('shop_id', fn ($t) => $t->unsignedBigInteger('shop_id')->nullable()->after('store_id'));
            $add('check_in_method', fn ($t) => $t->string('check_in_method', 20)->nullable()->after('check_out_at'));
            $add('check_out_method', fn ($t) => $t->string('check_out_method', 20)->nullable()->after('check_in_method'));
            $add('work_category', fn ($t) => $t->string('work_category', 20)->nullable()->after('check_out_method'));
            $add('work_seconds', fn ($t) => $t->unsignedBigInteger('work_seconds')->default(0)->after('hours_worked'));
            $add('break_seconds', fn ($t) => $t->unsignedBigInteger('break_seconds')->default(0)->after('work_seconds'));
            $add('task_seconds', fn ($t) => $t->unsignedBigInteger('task_seconds')->default(0)->after('break_seconds'));
            $add('on_break', fn ($t) => $t->boolean('on_break')->default(false)->after('task_seconds'));
            $add('created_by', fn ($t) => $t->unsignedBigInteger('created_by')->nullable()->after('note'));
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            foreach (['store_id', 'shop_id', 'check_in_method', 'check_out_method', 'work_category', 'work_seconds', 'break_seconds', 'task_seconds', 'on_break', 'created_by'] as $col) {
                if (Schema::hasColumn('attendances', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
