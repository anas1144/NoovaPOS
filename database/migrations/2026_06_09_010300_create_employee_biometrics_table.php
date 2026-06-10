<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-employee biometric enrolment.
 *
 *  - type 'face'        : `descriptors` holds the face-api.js 128-float
 *                         descriptors (JSON array of arrays) used for matching.
 *  - type 'fingerprint' : future-ready — stores camera sample references now;
 *                         hardware/WebAuthn matching can be added later.
 *
 * No raw images are required for matching (only the numeric descriptors), which
 * keeps the stored biometric data small and non-reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_biometrics', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->string('type', 20); // face | fingerprint
            $table->longText('descriptors')->nullable(); // JSON: face descriptors / sample refs
            $table->unsignedInteger('samples_count')->default(0);
            $table->string('status', 20)->default('enrolled'); // enrolled | pending | disabled
            $table->dateTime('last_registered_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_biometrics');
    }
};
