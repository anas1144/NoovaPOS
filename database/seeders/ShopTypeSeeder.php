<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\ShopType;
use Illuminate\Database\Seeder;

/**
 * Seeds the shop-type registry from Plan::SHOP_TYPES. Idempotent — preserves the
 * super admin's enabled/disabled choices on re-run.
 */
class ShopTypeSeeder extends Seeder
{
    public function run(): void
    {
        $i = 0;
        foreach (Plan::SHOP_TYPES as $key => $label) {
            ShopType::updateOrCreate(
                ['key' => $key],
                ['label' => $label, 'sort_order' => $i++]
            );
            // Note: 'enabled' is intentionally not overwritten on update.
        }
    }
}
