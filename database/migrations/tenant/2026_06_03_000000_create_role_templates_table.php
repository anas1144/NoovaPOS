<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Role templates — CENTRAL, super-admin-managed.
 *
 * The platform super admin defines reusable role+permission templates per shop
 * type. Tenant owners cannot edit raw permissions; they create a tenant role by
 * choosing a template (filtered to their shop types) and giving it a name. The
 * role then receives the template's permission set.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // machine slug, e.g. cashier_retail
            $table->string('display_name');         // human label, e.g. "Cashier"
            $table->string('shop_type', 60)->index(); // retail, restaurant, water_supply, …
            $table->json('permissions');            // array of permission names
            $table->boolean('is_system')->default(false); // seeded defaults, not deletable
            $table->timestamps();

            $table->unique(['name', 'shop_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_templates');
    }
};
