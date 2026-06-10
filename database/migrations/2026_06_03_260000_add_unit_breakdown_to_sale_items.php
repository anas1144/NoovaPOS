<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores the per-unit-level quantity breakdown of a sale line (e.g.
 * {"box":1,"piece":1}) so the invoice can show mixed-unit quantities while the
 * `quantity` column still holds the converted base quantity.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sale_items')) {
            return;
        }
        Schema::table('sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_items', 'unit_breakdown')) {
                $table->json('unit_breakdown')->nullable();
            }
            if (! Schema::hasColumn('sale_items', 'price_tier')) {
                $table->string('price_tier', 40)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            foreach (['unit_breakdown', 'price_tier'] as $col) {
                if (Schema::hasColumn('sale_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
