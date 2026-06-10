<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant baseline migration.
 *
 * Migrations in database/migrations/tenant run INSIDE each tenant's own
 * database (via `php artisan tenants:migrate`, or automatically on onboarding
 * through the TenantCreated job pipeline). They must NOT include a tenant_id
 * column — isolation is provided by the separate database itself.
 *
 * This baseline creates the minimal per-tenant tables. Move the business-domain
 * migrations (products, sales, purchases, inventory, etc.) into this folder as
 * the query layer is cut over from central tenant_id scoping to per-tenant DBs.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Per-tenant key/value settings store.
        if (! Schema::hasTable('tenant_settings')) {
            Schema::create('tenant_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        // Marker table recording when/how this tenant database was provisioned.
        if (! Schema::hasTable('tenant_provisioning')) {
            Schema::create('tenant_provisioning', function (Blueprint $table) {
                $table->id();
                $table->string('schema_version')->default('1.0.0');
                $table->timestamp('provisioned_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_provisioning');
        Schema::dropIfExists('tenant_settings');
    }
};
