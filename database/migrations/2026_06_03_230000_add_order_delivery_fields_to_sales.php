<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Order type + delivery fields on sales for restaurant / delivery flows.
 *   order_type: dine_in | takeaway | delivery
 *   delivery_status: pending | assigned | out_for_delivery | delivered | cancelled
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales')) {
            return;
        }
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'order_type')) {
                $table->string('order_type', 20)->default('dine_in')->after('price_tier');
            }
            if (! Schema::hasColumn('sales', 'delivery_boy_id')) {
                $table->unsignedBigInteger('delivery_boy_id')->nullable()->after('order_type');
            }
            if (! Schema::hasColumn('sales', 'delivery_address')) {
                $table->text('delivery_address')->nullable()->after('delivery_boy_id');
            }
            if (! Schema::hasColumn('sales', 'delivery_status')) {
                $table->string('delivery_status', 20)->nullable()->after('delivery_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            foreach (['order_type', 'delivery_boy_id', 'delivery_address', 'delivery_status'] as $col) {
                if (Schema::hasColumn('sales', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
