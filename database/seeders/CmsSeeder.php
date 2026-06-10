<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Seeds starter marketing CMS content: a landing page, one page per shop type
 * (shown in the top menu), and an FBR page. Idempotent (updateOrCreate by slug).
 */
class CmsSeeder extends Seeder
{
    public function run(): void
    {
        // Landing page.
        $landing = CmsPage::updateOrCreate(
            ['slug' => 'home'],
            [
                'title'        => 'Home',
                'type'         => 'landing',
                'show_in_menu' => false,
                'menu_order'   => 0,
                'seo_title'    => 'NoovaPOS — Multi-Tenant SaaS POS & ERP',
                'seo_description' => 'One platform for retail, restaurant, pharmacy, water supply and more.',
                'status'       => true,
                'is_system'    => true,
            ]
        );
        $this->section($landing->id, 'hero', 'html',
            '<h2 class="text-4xl font-bold">Run any business on one platform</h2>'.
            '<p class="mt-3 text-lg text-slate-600">Retail, restaurant, pharmacy, water supply, distribution and more — with offline-first POS, FBR (Pakistan), and a full ERP.</p>');

        // One menu page per shop type.
        $order = 1;
        foreach (Plan::SHOP_TYPES as $key => $label) {
            $page = CmsPage::updateOrCreate(
                ['slug' => 'pos/' . str_replace('_', '-', $key)],
                [
                    'title'        => $label,
                    'type'         => 'shop_type',
                    'shop_type'    => $key,
                    'show_in_menu' => true,
                    'menu_order'   => $order++,
                    'seo_title'    => $label . ' — NoovaPOS',
                    'seo_description' => $label . ' software with the features your business needs.',
                    'status'       => true,
                    'is_system'    => true,
                ]
            );
            $this->section($page->id, 'intro', 'html',
                '<h2 class="text-3xl font-bold">' . e($label) . '</h2>'.
                '<p class="mt-2 text-slate-600">Purpose-built features for ' . e(strtolower($label)) . '. See the plans available for this business type below.</p>');
        }

        // FBR page (Pakistan).
        $fbr = CmsPage::updateOrCreate(
            ['slug' => 'fbr'],
            [
                'title'        => 'FBR (Pakistan)',
                'type'         => 'fbr',
                'show_in_menu' => true,
                'menu_order'   => $order++,
                'seo_title'    => 'FBR Digital Invoicing — NoovaPOS',
                'seo_description' => 'FBR-ready POS for Pakistan: real-time digital invoicing, QR invoices and sync.',
                'status'       => true,
                'is_system'    => true,
            ]
        );
        $this->section($fbr->id, 'intro', 'html',
            '<h2 class="text-3xl font-bold">FBR Digital Invoicing</h2>'.
            '<p class="mt-2 text-slate-600">NoovaPOS integrates with FBR for real-time digital invoicing, QR-coded invoices and automatic sync — available to Pakistan tenants as an add-on.</p>',
            ['PK']);
    }

    private function section(int $pageId, string $key, string $type, string $content, ?array $countries = null): void
    {
        CmsSection::updateOrCreate(
            ['page_id' => $pageId, 'key' => $key],
            [
                'type'              => $type,
                'content'           => $content,
                'sort_order'        => 0,
                'visible_global'    => true,
                'visible_countries' => $countries,
                'status'            => true,
            ]
        );
    }
}
