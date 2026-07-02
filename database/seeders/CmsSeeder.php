<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Seeds marketing CMS content: a landing page, one rich page per shop type
 * (shown in the top menu), and the FBR pages. Idempotent (updateOrCreate by
 * slug/key), so it can be re-run to refresh copy without creating duplicates.
 */
class CmsSeeder extends Seeder
{
    /**
     * Tailored marketing copy per shop type.
     * Each entry: tagline, intro paragraph, and a list of [title, description]
     * feature cards that match the modules enabled for that business type.
     */
    private function shopContent(): array
    {
        return [
            'retail' => [
                'tagline'  => 'Fast, barcode-driven retail at any counter',
                'intro'    => 'Ring up sales in seconds with barcode scanning, keep stock accurate across every store, and reorder with confidence. NoovaPOS Retail is built for busy shops that need speed at the counter and control in the back office.',
                'features' => [
                    ['Lightning-fast billing', 'Barcode scanning, keyboard shortcuts and a touch-friendly cart designed for high-volume checkout.'],
                    ['Real-time inventory', 'Stock levels update on every sale, purchase and return — no more guessing what is on the shelf.'],
                    ['Purchases &amp; GRN', 'Raise purchase orders, receive goods (GRN) and reconcile supplier bills in one flow.'],
                    ['Supplier management', 'Track supplier ledgers, payments and purchase history in one place.'],
                    ['Multi-store transfers', 'Move stock between branches and warehouses with a full audit trail.'],
                    ['Reports that matter', 'Daily sales, profit, low-stock alerts and best-sellers at a glance.'],
                ],
            ],
            'restaurant' => [
                'tagline'  => 'From table to kitchen to bill — without the chaos',
                'intro'    => 'Manage halls and tables, fire orders straight to the kitchen, and split bills any way your guests want. NoovaPOS Restaurant keeps front-of-house and back-of-house perfectly in sync for dine-in, takeaway and delivery.',
                'features' => [
                    ['Halls &amp; tables', 'Visual floor plan with live table status so staff always know what is open, seated or ready to clear.'],
                    ['KOT &amp; kitchen display', 'Orders flow instantly to the kitchen display with states from open to cooking to served.'],
                    ['Waiter app', 'Take orders tableside on a phone or tablet and send them to the kitchen in one tap.'],
                    ['Split &amp; merge bills', 'Split by item, by guest or by share — and merge tables when plans change.'],
                    ['Dine-in, takeaway &amp; delivery', 'One system handles every order type with the right workflow for each.'],
                    ['Rider &amp; delivery tracking', 'Assign delivery orders to riders and keep tabs on what is out for delivery.'],
                ],
            ],
            'pharmacy' => [
                'tagline'  => 'Sell with confidence — every batch, every expiry tracked',
                'intro'    => 'Pharmacies cannot afford to sell expired stock or lose track of a batch. NoovaPOS Pharmacy tracks batches and expiry dates end-to-end, warns you before products lapse, and keeps your records audit-ready.',
                'features' => [
                    ['Batch tracking', 'Every item is tied to its batch from purchase to sale for full traceability.'],
                    ['Expiry control', 'Automatic near-expiry and expired-stock alerts so you act before it costs you.'],
                    ['Barcode billing', 'Scan and sell in seconds, with correct batch selection handled for you.'],
                    ['Inventory &amp; purchases', 'Keep accurate stock and reorder from suppliers without spreadsheets.'],
                    ['Sales &amp; returns', 'Handle returns cleanly while keeping batch and stock records correct.'],
                    ['Compliance-ready reports', 'Stock, sales and expiry reports you can rely on for records and audits.'],
                ],
            ],
            'water_supply' => [
                'tagline'  => 'Recurring water delivery, billing and bottles — automated',
                'intro'    => 'Run a water delivery business without the manual chasing. NoovaPOS Water Supply auto-generates delivery schedules and monthly invoices, tracks bottles and deposits, and organises drivers by route.',
                'features' => [
                    ['Recurring deliveries', 'Set delivery days per customer (e.g. Mon/Wed/Fri) and let the system build the schedule.'],
                    ['Route management', 'Group customers into routes and assign drivers for efficient daily runs.'],
                    ['Bottle &amp; deposit tracking', 'Know exactly how many bottles each customer holds and manage refundable deposits.'],
                    ['Auto monthly invoices', 'Invoices generate automatically from deliveries, including extra or skipped days.'],
                    ['Delivery scheduling', 'Handle extra-bottle requests and skipped deliveries without breaking billing.'],
                    ['Due reminders', 'Track overdue accounts and follow up before they pile up.'],
                ],
            ],
            'bakery' => [
                'tagline'  => 'Bake, stock and sell — all in one counter',
                'intro'    => 'NoovaPOS Bakery pairs a fast retail counter with production tracking, so you can manage what you bake, what you stock and what you sell without missing a beat.',
                'features' => [
                    ['Production tracking', 'Plan and record daily production so the counter always reflects what is fresh.'],
                    ['Fast counter sales', 'Quick, touch-friendly billing built for peak morning rushes.'],
                    ['Inventory control', 'Track ingredients and finished goods with accurate, real-time stock.'],
                    ['Purchases', 'Order ingredients and supplies and keep supplier records tidy.'],
                    ['Sales insights', 'See best-sellers and daily takings to plan tomorrow’s batch.'],
                    ['Multi-outlet ready', 'Run several outlets on one platform with shared products and reporting.'],
                ],
            ],
            'electronics' => [
                'tagline'  => 'Serial numbers and warranties, handled properly',
                'intro'    => 'Selling electronics means tracking individual units and honouring warranties. NoovaPOS Electronics captures serial numbers at every step and keeps warranty records tied to each sale.',
                'features' => [
                    ['Serial number tracking', 'Capture and trace serials from purchase through to the customer who bought them.'],
                    ['Warranty tracking', 'Link warranties to sales so claims and returns are quick and accurate.'],
                    ['Barcode billing', 'Scan to sell and keep checkout fast and error-free.'],
                    ['Inventory &amp; purchases', 'Accurate stock and easy reordering across your catalogue.'],
                    ['Sales &amp; returns', 'Process exchanges and returns while keeping serial records intact.'],
                    ['Detailed reports', 'Know your margins, movers and stock position at any time.'],
                ],
            ],
            'fashion' => [
                'tagline'  => 'Every size and colour, perfectly in stock',
                'intro'    => 'Apparel lives and dies by variations. NoovaPOS Fashion handles size and colour matrices, barcodes every variant, and keeps your inventory exact across the whole range.',
                'features' => [
                    ['Variation matrix', 'Manage size and colour combinations as proper, scannable variants.'],
                    ['Barcode per variant', 'Each variation gets its own barcode for fast, accurate selling.'],
                    ['Inventory control', 'Track stock by variant so you never oversell a popular size.'],
                    ['Purchases', 'Buy and receive across sizes and colours in one purchase flow.'],
                    ['Sales &amp; returns', 'Smooth exchanges and returns that keep variant stock correct.'],
                    ['Style insights', 'See which styles, sizes and colours sell — and which to discount.'],
                ],
            ],
            'distribution' => [
                'tagline'  => 'Move stock and serve routes at scale',
                'intro'    => 'NoovaPOS Distribution is built for businesses supplying many customers. Organise deliveries by route, transfer stock between locations, and bill at volume — with full visibility throughout.',
                'features' => [
                    ['Route management', 'Plan delivery routes and assign drivers for efficient daily distribution.'],
                    ['Stock transfers', 'Move inventory between warehouses and vans with a clear audit trail.'],
                    ['Delivery scheduling', 'Schedule recurring and one-off deliveries to your customer base.'],
                    ['Bulk sales &amp; purchases', 'Handle high-volume orders and supplier purchasing with ease.'],
                    ['Inventory control', 'Real-time stock across every location keeps fulfilment accurate.'],
                    ['Reports', 'Track sales, deliveries and stock movement to spot trends early.'],
                ],
            ],
            'monthly_service' => [
                'tagline'  => 'Recurring billing that runs itself',
                'intro'    => 'For subscription and monthly-service businesses, NoovaPOS automates the billing cycle end-to-end: generate recurring invoices, manage subscriptions and stay on top of overdue accounts.',
                'features' => [
                    ['Recurring invoices', 'Invoices generate automatically on each customer’s billing cycle.'],
                    ['Subscriptions', 'Manage plans, start/stop dates and changes without manual rework.'],
                    ['Flexible billing cycles', 'Monthly, quarterly or custom cycles to fit your service.'],
                    ['Overdue tracking', 'See who is late and follow up before revenue slips.'],
                    ['Customer management', 'Keep contracts, history and balances in one customer record.'],
                    ['Clear reporting', 'Understand recurring revenue and collections at a glance.'],
                ],
            ],
            'fbr_digital' => [
                'tagline'  => 'Dedicated FBR Digital Invoicing — no POS required',
                'intro'    => 'For businesses that only need FBR-compliant invoices, NoovaPOS FBR Digital Invoice provides invoice generation, real-time sync, error tracking and sandbox testing — for a single company or, as an agent, for many.',
                'features' => [
                    ['FBR invoice generation', 'Create FBR-registered invoices with the fields and validation FBR expects.'],
                    ['Real-time sync', 'Submit and sync invoices to FBR, with the FBR invoice number returned on success.'],
                    ['Error centre', 'Catch and fix rejected invoices with clear, actionable error messages.'],
                    ['Sandbox testing', 'Validate against FBR sandbox scenarios before going live.'],
                    ['QR codes &amp; reports', 'Generate QR-coded invoices and pull sales, tax and sync reports.'],
                    ['Multi-business &amp; agent support', 'Manage invoicing for many companies from one account.'],
                ],
            ],
            'custom' => [
                'tagline'  => 'Your business, your modules',
                'intro'    => 'Not a standard shop type? Start from a flexible base — POS, inventory, sales, purchases and reports — and switch on exactly the features your business needs. Talk to us about a custom configuration.',
                'features' => [
                    ['Flexible POS', 'A solid selling foundation you can shape to your workflow.'],
                    ['Inventory control', 'Accurate, real-time stock whatever you sell.'],
                    ['Sales &amp; purchases', 'Core buy/sell flows ready out of the box.'],
                    ['Reports', 'The numbers you need to run the business.'],
                    ['Add-on modules', 'Enable extras — accounting, CRM, HR, FBR and more — as you grow.'],
                    ['Built to scale', 'Multi-store, multi-user and offline-first from day one.'],
                ],
            ],
        ];
    }

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
        $this->section($landing->id, 'hero', 'html', 0,
            '<h2 class="text-4xl font-bold">Run any business on one platform</h2>'.
            '<p class="mt-3 text-lg text-slate-600">Retail, restaurant, pharmacy, water supply, distribution and more — with offline-first POS, FBR (Pakistan), and a full ERP.</p>');

        $shopContent = $this->shopContent();

        // One menu page per shop type, with tailored content.
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
                    'seo_description' => ($shopContent[$key]['intro'] ?? ($label . ' software with the features your business needs.')),
                    'status'       => true,
                    'is_system'    => true,
                ]
            );

            $c = $shopContent[$key] ?? null;

            // FBR Digital Invoice is a Pakistan-only product — restrict its
            // content to PK visitors, like the other FBR pages.
            $countries = $key === 'fbr_digital' ? ['PK'] : null;

            // Hero / intro (key kept as "intro" so older seeded rows are updated).
            $this->section($page->id, 'intro', 'html', 0,
                '<p class="text-indigo-600 font-semibold uppercase tracking-wide text-sm">'.e($label).'</p>'.
                '<h2 class="mt-1 text-3xl md:text-4xl font-bold text-slate-900">'.e($c['tagline'] ?? $label).'</h2>'.
                '<p class="mt-3 text-lg text-slate-600 max-w-3xl">'.($c['intro'] ?? '').'</p>',
                $countries);

            // Feature grid.
            if ($c && ! empty($c['features'])) {
                $cards = '';
                foreach ($c['features'] as [$title, $desc]) {
                    $cards .=
                        '<div class="rounded-xl border border-slate-200 bg-white p-5">'.
                        '<div class="flex items-start gap-3">'.
                        '<span class="mt-0.5 inline-flex h-6 w-6 flex-none items-center justify-center rounded-full bg-indigo-50 text-indigo-600 text-sm font-bold">&#10003;</span>'.
                        '<div><h3 class="font-semibold text-slate-900">'.$title.'</h3>'.
                        '<p class="mt-1 text-sm text-slate-600">'.$desc.'</p></div>'.
                        '</div></div>';
                }
                $this->section($page->id, 'features', 'html', 1,
                    '<h3 class="text-2xl font-bold text-slate-900 mb-5">What you get</h3>'.
                    '<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">'.$cards.'</div>',
                    $countries);
            }

            // Shared platform section.
            $this->section($page->id, 'platform', 'html', 2,
                '<div class="rounded-2xl bg-slate-900 text-white p-8">'.
                '<h3 class="text-2xl font-bold">Powered by the NoovaPOS platform</h3>'.
                '<p class="mt-2 text-slate-300 max-w-3xl">Every business type runs on the same enterprise foundation: offline-first POS that keeps selling without internet, multi-store and multi-user support, role-based permissions, a full accounting and reporting suite, and FBR digital invoicing for Pakistan.</p>'.
                '<div class="mt-5 flex flex-wrap gap-2 text-sm">'.
                '<span class="rounded-full bg-white/10 px-3 py-1">Offline-first</span>'.
                '<span class="rounded-full bg-white/10 px-3 py-1">Multi-store</span>'.
                '<span class="rounded-full bg-white/10 px-3 py-1">Role-based access</span>'.
                '<span class="rounded-full bg-white/10 px-3 py-1">Accounting &amp; reports</span>'.
                '<span class="rounded-full bg-white/10 px-3 py-1">FBR-ready (PK)</span>'.
                '</div></div>',
                $countries);
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
        $this->section($fbr->id, 'intro', 'html', 0,
            '<p class="text-indigo-600 font-semibold uppercase tracking-wide text-sm">For Pakistan</p>'.
            '<h2 class="mt-1 text-3xl md:text-4xl font-bold text-slate-900">FBR Digital Invoicing, built in</h2>'.
            '<p class="mt-3 text-lg text-slate-600 max-w-3xl">NoovaPOS integrates with FBR for real-time digital invoicing, QR-coded invoices and automatic sync — available to Pakistan tenants as an add-on on top of any POS.</p>',
            ['PK']);
        $this->section($fbr->id, 'features', 'html', 1,
            '<div class="grid gap-4 sm:grid-cols-2">'.
            $this->fbrCard('Real-time digital invoicing', 'Invoices are submitted and synced to FBR as you sell.').
            $this->fbrCard('QR-coded invoices', 'Compliant QR codes generated automatically on every invoice.').
            $this->fbrCard('Automatic sync &amp; retry', 'A background queue syncs invoices and retries failures safely.').
            $this->fbrCard('Sandbox &amp; production', 'Test against FBR sandbox, then switch to production when ready.').
            '</div>',
            ['PK']);

        // Dedicated "FBR Digital Invoicing" product page (standalone shop type).
        $diPage = CmsPage::updateOrCreate(
            ['slug' => 'fbr-digital-invoicing'],
            [
                'title'        => 'FBR Digital Invoicing',
                'type'         => 'fbr',
                'show_in_menu' => true,
                'menu_order'   => $order++,
                'seo_title'    => 'FBR Digital Invoicing Software — NoovaPOS',
                'seo_description' => 'Dedicated FBR Digital Invoicing: generation, integration, real-time sync, error tracking, sandbox testing, QR codes, compliance reports and multi-business support.',
                'status'       => true,
                'is_system'    => true,
            ]
        );
        $this->section($diPage->id, 'intro', 'html', 0,
            '<p class="text-indigo-600 font-semibold uppercase tracking-wide text-sm">For Pakistan</p>'.
            '<h2 class="mt-1 text-3xl md:text-4xl font-bold text-slate-900">FBR Digital Invoicing — without a POS</h2>'.
            '<p class="mt-3 text-lg text-slate-600 max-w-3xl">A dedicated FBR Digital Invoicing solution with no POS billing required. Create, sync and manage FBR-registered invoices for one company or, as an agent, for many.</p>',
            ['PK']);
        $this->section($diPage->id, 'features', 'html', 1,
            '<div class="grid gap-4 sm:grid-cols-2">'.
            $this->fbrCard('FBR registered invoice generation', 'Create compliant invoices with full validation.').
            $this->fbrCard('FBR integration &amp; real-time sync', 'Submit to FBR and receive the official invoice number on success.').
            $this->fbrCard('Error tracking &amp; sandbox testing', 'Resolve rejections fast and validate against sandbox scenarios.').
            $this->fbrCard('QR code generation', 'Compliant QR codes on every invoice, automatically.').
            $this->fbrCard('FBR compliance reports', 'Sales, tax, sync and rejected-invoice reporting.').
            $this->fbrCard('Multi-business &amp; agent support', 'Manage invoicing for many companies from one account.').
            '</div>',
            ['PK']);
    }

    private function fbrCard(string $title, string $desc): string
    {
        return '<div class="rounded-xl border border-slate-200 bg-white p-5">'.
            '<h3 class="font-semibold text-slate-900">'.$title.'</h3>'.
            '<p class="mt-1 text-sm text-slate-600">'.$desc.'</p></div>';
    }

    private function section(int $pageId, string $key, string $type, int $sort, string $content, ?array $countries = null): void
    {
        CmsSection::updateOrCreate(
            ['page_id' => $pageId, 'key' => $key],
            [
                'type'              => $type,
                'content'           => $content,
                'sort_order'        => $sort,
                'visible_global'    => true,
                'visible_countries' => $countries,
                'status'            => true,
            ]
        );
    }
}
