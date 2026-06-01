<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('business_name')->nullable();
            $table->string('business_type', 100)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new'); // new, contacted, converted, closed
            $table->string('ip_address', 45)->nullable();
            $table->string('country', 5)->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable(); // super admin user id
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_requests');
    }
};
