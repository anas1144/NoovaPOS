<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FBR DI sandbox test scenarios (SN001…SN028) with last run result/response.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fbr_sandbox_scenarios', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->string('code', 20)->index();   // SN001…
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 12)->default('untested'); // untested | pass | fail
            $table->text('last_result')->nullable();
            $table->longText('response')->nullable();
            $table->dateTime('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fbr_sandbox_scenarios');
    }
};
