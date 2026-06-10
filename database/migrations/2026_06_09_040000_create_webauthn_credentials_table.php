<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WebAuthn (passkey / platform authenticator) credentials per employee.
 *
 * Lets a laptop/phone's own fingerprint or face sensor be used for attendance —
 * real on-device biometric matching, no external hardware. We store the public
 * key (DER SPKI, base64) provided by the browser at registration; the server
 * verifies each login assertion's signature with openssl.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webauthn_credentials', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->string('credential_id', 255)->unique(); // base64url raw id
            $table->text('public_key');                     // base64 DER SPKI
            $table->integer('algorithm')->default(-7);      // COSE alg (ES256 = -7, RS256 = -257)
            $table->unsignedBigInteger('sign_count')->default(0);
            $table->string('label', 120)->nullable();       // e.g. "Front desk laptop"
            $table->dateTime('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webauthn_credentials');
    }
};
