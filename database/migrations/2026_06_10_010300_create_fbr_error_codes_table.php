<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FBR error-code reference + occurrence tracking for the Error Center.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fbr_error_codes', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->string('code', 30)->index();
            $table->string('message')->nullable();
            $table->text('description')->nullable();
            $table->text('resolution')->nullable();
            $table->dateTime('last_occurrence_at')->nullable();
            $table->unsignedBigInteger('total_occurrences')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fbr_error_codes');
    }
};
