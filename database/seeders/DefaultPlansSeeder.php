<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class DefaultPlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Single branch POS for small shops.',
                'price' => 0,
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'max_stores' => 1,
                'max_shops' => 1,
                'max_registers' => 1,
                'max_users' => 3,
                'max_products' => 500,
                'features' => ['pos', 'stock', 'basic_reports'],
                'status' => true,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'Multi-branch POS with stronger reporting.',
                'price' => 49,
                'billing_cycle' => 'monthly',
                'trial_days' => 14,
                'max_stores' => 5,
                'max_shops' => 10,
                'max_registers' => 20,
                'max_users' => 25,
                'max_products' => 10000,
                'features' => ['pos', 'stock', 'transfers', 'reports', 'offline_sync'],
                'status' => true,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited tenant plan for larger operations.',
                'price' => 199,
                'billing_cycle' => 'monthly',
                'trial_days' => 0,
                'max_stores' => null,
                'max_shops' => null,
                'max_registers' => null,
                'max_users' => null,
                'max_products' => null,
                'features' => ['pos', 'stock', 'transfers', 'advanced_reports', 'fbr', 'offline_sync', 'priority_support'],
                'status' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
