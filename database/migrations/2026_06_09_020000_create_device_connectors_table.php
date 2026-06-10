<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * No-code biometric / scanner device integration.
 *
 * A connector describes HOW to talk to an external device or service so a new
 * device can be added from the UI without code changes:
 *
 *  - run_on 'server' : the platform calls `base_url + verify_path` (cloud APIs,
 *    networked terminals like ZKTeco/Hikvision, AWS Rekognition, Azure Face).
 *  - run_on 'client' : the kiosk browser calls a LOCAL agent
 *    (http://localhost:port) shipped with USB scanners (Mantra/SecuGen/ZKTeco
 *    SDK). The browser does the call so localhost is reachable.
 *
 * request_template is a JSON body with {{placeholders}} (employee_id, descriptor,
 * sample, image, code…). response_employee_path / response_success_path are dot
 * paths read from the device's JSON response to resolve the matched employee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_connectors', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->string('name', 120);
            $table->string('modality', 20);            // face | fingerprint | card | rfid | nfc
            $table->string('transport', 20)->default('http');
            $table->string('run_on', 10)->default('server'); // server | client
            $table->string('base_url', 255)->nullable();
            $table->string('verify_path', 255)->nullable();
            $table->string('enroll_path', 255)->nullable();
            $table->string('http_method', 8)->default('POST');
            $table->string('auth_type', 20)->default('none'); // none | api_key | bearer | basic
            $table->string('auth_header', 60)->nullable();     // e.g. X-API-Key / Authorization
            $table->text('auth_token')->nullable();
            $table->json('headers')->nullable();
            $table->longText('request_template')->nullable();  // JSON with {{placeholders}}
            $table->string('response_success_path', 120)->nullable(); // dot path, e.g. data.matched
            $table->string('success_value', 60)->nullable();          // value that means success
            $table->string('response_employee_path', 120)->nullable();// dot path to employee id/code
            $table->unsignedInteger('timeout')->default(15);
            $table->boolean('status')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_connectors');
    }
};
