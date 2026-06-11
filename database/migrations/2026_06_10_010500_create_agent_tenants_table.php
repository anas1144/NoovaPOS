<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agent → Company mapping. An "Agent" user manages several Companies (= tenants)
 * for the FBR Digital Invoice plan type. Central pivot (agent user id ↔ tenant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_tenants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_user_id')->index();
            $table->uuid('tenant_id')->index();
            $table->timestamps();

            $table->unique(['agent_user_id', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_tenants');
    }
};
