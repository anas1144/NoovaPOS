<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kitchens for restaurant-type stores. A tenant adds kitchens under a store;
 * halls and waiters are linked to a kitchen so KOTs route to the right place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('store_id')->index();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchens');
    }
};
