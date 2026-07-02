<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds 22 starter marketing blog posts (central). Idempotent (updateOrCreate
 * by slug) so it can be re-run to refresh copy without creating duplicates.
 *
 * Content uses explicit Tailwind classes because the public CMS layout loads
 * the Tailwind CDN (with preflight), which strips default heading/list styles.
 */
class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $authors = ['NoovaPOS Team', 'Ayesha Khan', 'Bilal Ahmed', 'Sara Malik'];

        foreach ($this->posts() as $i => $post) {
            BlogPost::updateOrCreate(
                ['slug' => $post['slug']],
                [
                    'title'           => $post['title'],
                    'cover_image'     => 'https://picsum.photos/seed/noova-' . ($i + 1) . '/800/400',
                    'excerpt'         => $post['excerpt'],
                    'content'         => $this->body($post['sections']),
                    'author'          => $authors[$i % count($authors)],
                    'seo_title'       => $post['title'] . ' — NoovaPOS',
                    'seo_description' => Str::limit($post['excerpt'], 155),
                    'seo_keywords'    => $post['keywords'] ?? 'POS, ERP, NoovaPOS',
                    'status'          => true,
                    // Newest first; spaced four days apart.
                    'published_at'    => now()->subDays($i * 4),
                ]
            );
        }
    }

    /** Build post HTML from an ordered list of [heading, body] sections. */
    private function body(array $sections): string
    {
        $html = '';
        foreach ($sections as [$heading, $content]) {
            if ($heading !== '') {
                $html .= '<h2 class="text-2xl font-bold text-slate-900 mt-8 mb-2">' . $heading . '</h2>';
            }
            if (is_array($content)) {
                $html .= '<ul class="list-disc pl-6 mt-3 space-y-1 text-slate-700">';
                foreach ($content as $li) {
                    $html .= '<li>' . $li . '</li>';
                }
                $html .= '</ul>';
            } else {
                $html .= '<p class="mt-3 text-slate-700 leading-relaxed">' . $content . '</p>';
            }
        }
        return $html;
    }

    private function posts(): array
    {
        return [
            [
                'slug' => 'why-offline-first-pos-matters',
                'title' => 'Why Offline-First POS Matters for Retailers',
                'keywords' => 'offline POS, POS without internet, retail software',
                'excerpt' => 'Internet drops, but sales should not. Here is why an offline-first point of sale is essential for any serious retail business.',
                'sections' => [
                    ['', 'Every retailer has lived it: a queue at the counter, a card in hand, and the internet suddenly gone. With a cloud-only POS, the till stops. With an offline-first POS, nothing changes.'],
                    ['What “offline-first” really means', 'An offline-first system treats the local device as the source of truth during a sale. Products, prices and the cart all live locally, so checkout never waits on the network. When the connection returns, everything syncs automatically.'],
                    ['The risks of a cloud-only till', [
                        'Lost sales every time the connection drops.',
                        'Frustrated customers and longer queues.',
                        'Staff falling back to pen and paper, then re-keying later.',
                    ]],
                    ['How NoovaPOS handles it', 'NoovaPOS stores products and sales locally, generates a unique ID for each offline sale, and queues them for background sync. Idempotency checks on the server make sure nothing is ever double-counted when the device reconnects.'],
                ],
            ],
            [
                'slug' => 'fbr-digital-invoicing-guide',
                'title' => 'A Complete Guide to FBR Digital Invoicing',
                'keywords' => 'FBR, digital invoicing, Pakistan tax, FBR POS',
                'excerpt' => 'FBR digital invoicing is now part of doing business in Pakistan. Here is a plain-language guide to what it is and how to stay compliant.',
                'sections' => [
                    ['', 'If you sell in Pakistan, FBR digital invoicing is moving from “nice to have” to “required”. The good news: with the right software it is mostly automatic.'],
                    ['What FBR digital invoicing involves', 'Each invoice is registered with the Federal Board of Revenue in real time, returns an official invoice number, and carries a compliant QR code. Your POS handles the submission so your staff do not have to.'],
                    ['What you need to get started', [
                        'Your business NTN and STRN.',
                        'Province and business activity details.',
                        'A POS ID and the relevant sandbox or production tokens.',
                    ]],
                    ['Sandbox first, then production', 'A good workflow tests invoices against FBR’s sandbox scenarios before going live. NoovaPOS includes sandbox testing, an error centre for rejected invoices, and a queue that retries failed submissions safely.'],
                ],
            ],
            [
                'slug' => 'choosing-restaurant-pos',
                'title' => 'Choosing the Right POS for Your Restaurant',
                'keywords' => 'restaurant POS, KOT, dine-in software',
                'excerpt' => 'Restaurants have needs a retail till simply cannot meet. Here is what to look for when picking a restaurant POS.',
                'sections' => [
                    ['', 'A restaurant is not a shop with food. Tables, kitchens, waiters and split bills all need to work together, and your POS should reflect that.'],
                    ['Must-have features', [
                        'A visual floor plan with live table status.',
                        'Kitchen order tickets (KOT) sent straight to a kitchen display.',
                        'Split and merge bills for groups.',
                        'Support for dine-in, takeaway and delivery in one system.',
                    ]],
                    ['Speed during the rush', 'The dinner rush is where systems break. Look for a waiter app that fires orders from the table in one tap, and a kitchen display that tracks each dish from open to cooking to served.'],
                    ['Why NoovaPOS fits', 'NoovaPOS Restaurant brings halls, tables, KOT, waiter ordering and split billing together, so front-of-house and the kitchen stay perfectly in sync.'],
                ],
            ],
            [
                'slug' => 'inventory-mistakes-that-cost-money',
                'title' => 'Inventory Mistakes That Cost Retailers Money',
                'keywords' => 'inventory management, stock control, retail',
                'excerpt' => 'Poor inventory habits quietly drain profit. Here are the most common mistakes and how to fix them.',
                'sections' => [
                    ['', 'Inventory is usually a retailer’s biggest asset and biggest source of leakage. Small process gaps add up to real money over a year.'],
                    ['The usual culprits', [
                        'No single, real-time view of stock across locations.',
                        'Manual counts that are out of date the moment they are done.',
                        'No reorder points, so best-sellers run out.',
                        'Untracked returns and adjustments.',
                    ]],
                    ['Make stock movements the source of truth', 'The fix is to treat every purchase, sale, return, transfer and adjustment as a recorded stock movement. That gives you an accurate ledger you can trust and audit.'],
                    ['Automate the alerts', 'NoovaPOS updates stock on every transaction and raises low-stock alerts before you run out, so reordering becomes a decision rather than a fire drill.'],
                ],
            ],
            [
                'slug' => 'multi-store-in-sync',
                'title' => 'How Multi-Store Businesses Stay in Sync',
                'keywords' => 'multi-store POS, branches, central management',
                'excerpt' => 'Running several branches multiplies the complexity. One platform keeps prices, stock and reporting consistent everywhere.',
                'sections' => [
                    ['', 'Two stores are more than twice the work if every branch runs its own island of data. The goal is one source of truth with local flexibility.'],
                    ['What “in sync” looks like', [
                        'Shared products and prices, with per-store overrides where needed.',
                        'Stock transfers between branches with a full audit trail.',
                        'Consolidated reporting across the whole business.',
                    ]],
                    ['Roles keep it under control', 'Branch managers see their store; head office sees everything. Role-based permissions make sure people only touch what they should.'],
                    ['The NoovaPOS hierarchy', 'NoovaPOS models your business as tenant → stores → shops → registers → users, so a growing chain stays organised instead of chaotic.'],
                ],
            ],
            [
                'slug' => 'water-delivery-recurring-billing',
                'title' => 'Running a Water Delivery Business: Recurring Billing Done Right',
                'keywords' => 'water supply POS, recurring billing, delivery scheduling',
                'excerpt' => 'Water delivery lives on routines: regular routes, regular bottles, regular invoices. Automation makes it effortless.',
                'sections' => [
                    ['', 'A water supply business is a scheduling and billing business. Get those right and the rest follows.'],
                    ['The moving parts', [
                        'Recurring deliveries on chosen days (e.g. Mon/Wed/Fri).',
                        'Bottle and refundable deposit tracking per customer.',
                        'Routes assigned to drivers for efficient runs.',
                        'Monthly invoices generated automatically from deliveries.',
                    ]],
                    ['Handle the exceptions', 'Real life includes extra-bottle requests and skipped days. Your system should fold those into billing without manual correction.'],
                    ['How NoovaPOS automates it', 'NoovaPOS auto-generates the delivery schedule and invoices, tracks bottles and deposits, and flags overdue accounts before they pile up.'],
                ],
            ],
            [
                'slug' => 'pharmacy-batch-expiry-tracking',
                'title' => 'Pharmacy POS: Tracking Batches and Expiry the Smart Way',
                'keywords' => 'pharmacy POS, batch tracking, expiry management',
                'excerpt' => 'In a pharmacy, a missed expiry date is more than lost stock — it is a compliance and safety risk.',
                'sections' => [
                    ['', 'Pharmacies carry hundreds of products, each with its own batches and expiry dates. Tracking them by hand does not scale.'],
                    ['Why batch-level tracking matters', 'Tying every item to its batch from purchase to sale gives you full traceability. If a batch is recalled, you know exactly where it went.'],
                    ['Stay ahead of expiry', [
                        'Automatic near-expiry alerts so you can act early.',
                        'Blocked or flagged sales for expired stock.',
                        'Clean records for audits and inspections.',
                    ]],
                    ['NoovaPOS Pharmacy', 'NoovaPOS tracks batches and expiry end-to-end, warns you before products lapse, and keeps your sales fast with barcode billing.'],
                ],
            ],
            [
                'slug' => 'barcode-scanning-101',
                'title' => 'Barcode Scanning 101: Speeding Up Your Checkout',
                'keywords' => 'barcode, checkout speed, retail POS',
                'excerpt' => 'A few seconds per item adds up across a busy day. Barcode scanning is the simplest way to speed up the till.',
                'sections' => [
                    ['', 'Manual lookups are the silent killer of checkout speed. Barcodes turn each line item into a single scan.'],
                    ['Where the time goes', 'Typing a product name, scrolling a list, confirming the price — repeat that hundreds of times a day and the cost is enormous. Scanning collapses it to an instant.'],
                    ['Getting started', [
                        'Assign a barcode to every product (or print your own).',
                        'Use a USB or Bluetooth scanner with your POS.',
                        'Combine scanning with keyboard shortcuts for even faster entry.',
                    ]],
                    ['Built for speed', 'NoovaPOS pairs barcode scanning with a fast cart, keyboard shortcuts and virtualised product lists that stay quick even with 100,000+ items.'],
                ],
            ],
            [
                'slug' => 'understanding-saas-subscriptions',
                'title' => 'Understanding SaaS Subscriptions: Plans, Trials and Add-ons',
                'keywords' => 'SaaS, subscription, pricing plans',
                'excerpt' => 'Free trials, monthly plans, add-ons and limits — here is how modern software subscriptions actually work.',
                'sections' => [
                    ['', 'Software has shifted from one-time purchases to subscriptions. Understanding the model helps you pick the right plan and avoid surprises.'],
                    ['Common building blocks', [
                        'Free trials to test before you commit.',
                        'Monthly or yearly billing, often with a discount for yearly.',
                        'Limits on stores, users, products or storage.',
                        'Add-ons for extra modules like accounting or CRM.',
                    ]],
                    ['Upgrade as you grow', 'A good platform lets you upgrade, downgrade or add modules without migrating data. You pay for what you need today and scale up later.'],
                    ['The NoovaPOS approach', 'NoovaPOS offers trials, flexible plans and per-module add-ons, so a single shop and a multi-branch chain can both find a fair fit.'],
                ],
            ],
            [
                'slug' => 'restaurant-kot-workflow',
                'title' => 'The Restaurant KOT Workflow Explained',
                'keywords' => 'KOT, kitchen display, restaurant operations',
                'excerpt' => 'The Kitchen Order Ticket is the heartbeat of a busy restaurant. Here is how a clean KOT workflow keeps service smooth.',
                'sections' => [
                    ['', 'A KOT — Kitchen Order Ticket — is how the floor talks to the kitchen. When it works, food comes out hot and in order. When it does not, chaos follows.'],
                    ['The states of an order', [
                        'Open — the order is being built at the table.',
                        'Sent — fired to the kitchen display.',
                        'Cooking — the kitchen is preparing it.',
                        'Ready — waiting to be picked up.',
                        'Served — delivered to the guest.',
                    ]],
                    ['Why a digital KOT wins', 'Paper tickets get lost, smudged and reordered. A kitchen display shows every order in real time, with clear timing so nothing is forgotten.'],
                    ['NoovaPOS in the kitchen', 'NoovaPOS sends orders to the kitchen display the moment they are placed, and supports delta reprints so only newly added items are fired.'],
                ],
            ],
            [
                'slug' => 'spreadsheets-to-erp',
                'title' => 'From Spreadsheets to ERP: When to Make the Switch',
                'keywords' => 'ERP, spreadsheets, business software',
                'excerpt' => 'Spreadsheets are where most businesses start — and where many get stuck. Here is how to know it is time to move on.',
                'sections' => [
                    ['', 'Spreadsheets are flexible and familiar, which is exactly why businesses cling to them long after they have outgrown them.'],
                    ['Signs you have outgrown them', [
                        'Different people keep different versions of the truth.',
                        'Reports take hours to assemble by hand.',
                        'Stock, sales and accounts do not reconcile.',
                        'Mistakes are common and hard to trace.',
                    ]],
                    ['What an ERP adds', 'An ERP connects sales, inventory, purchasing and accounting so a single transaction updates everything at once — accurately, and with an audit trail.'],
                    ['Making the move easier', 'NoovaPOS includes queue-based imports that survive refreshes and logouts, so bringing your existing data across is far less painful than it sounds.'],
                ],
            ],
            [
                'slug' => 'accounting-automation-auto-posting',
                'title' => 'Accounting Automation: Auto-Posting Sales and Purchases',
                'keywords' => 'accounting, auto-posting, journal entries',
                'excerpt' => 'Manual bookkeeping is slow and error-prone. Auto-posting turns every transaction into clean accounting entries automatically.',
                'sections' => [
                    ['', 'Most accounting errors are not fraud — they are tired humans re-typing numbers. Automation removes that step entirely.'],
                    ['What auto-posting does', 'When a sale, purchase, expense or refund happens, the system writes the matching journal entries to your chart of accounts in real time.'],
                    ['What you get out of it', [
                        'An always-current trial balance.',
                        'Profit and loss and balance sheet on demand.',
                        'Customer and vendor ledgers that reconcile.',
                    ]],
                    ['NoovaPOS accounting', 'NoovaPOS auto-posts from sales, purchases, expenses and refunds, so your books stay current without a second round of data entry.'],
                ],
            ],
            [
                'slug' => 'reduce-stockouts-reorder-points',
                'title' => 'How to Reduce Stockouts with Smart Reorder Points',
                'keywords' => 'reorder point, stockout, inventory planning',
                'excerpt' => 'A stockout on a best-seller is a sale handed to your competitor. Reorder points keep your shelves full.',
                'sections' => [
                    ['', 'Running out of a popular product is one of the most expensive mistakes in retail — and one of the most avoidable.'],
                    ['What is a reorder point?', 'It is the stock level at which you should reorder, based on how fast an item sells and how long resupply takes. Hit it, and a purchase suggestion appears.'],
                    ['Set yourself up to win', [
                        'Track real sales velocity per product.',
                        'Factor in supplier lead times.',
                        'Get alerts before you hit zero, not after.',
                    ]],
                    ['Smarter with NoovaPOS', 'NoovaPOS raises low-stock alerts and can suggest reorders based on your sales history, so fast-movers stay in stock.'],
                ],
            ],
            [
                'slug' => 'serial-numbers-warranty-electronics',
                'title' => 'Serial Numbers and Warranty Tracking for Electronics Stores',
                'keywords' => 'electronics POS, serial number, warranty',
                'excerpt' => 'Electronics retail is unit-level retail. If you cannot track a serial number, you cannot honour a warranty cleanly.',
                'sections' => [
                    ['', 'A phone is not just “a phone” — it is a specific unit with a specific serial and a specific warranty. Your system needs to think the same way.'],
                    ['Why serial tracking matters', 'Capturing serials from purchase to sale means you know exactly which unit went to which customer, which makes returns and warranty claims fast and dispute-free.'],
                    ['Warranty without the headache', [
                        'Link a warranty period to each sale.',
                        'Look up coverage instantly by serial.',
                        'Handle exchanges while keeping records intact.',
                    ]],
                    ['NoovaPOS Electronics', 'NoovaPOS captures serial numbers and warranty details at every step, so service and returns stay simple.'],
                ],
            ],
            [
                'slug' => 'managing-variations-fashion-retail',
                'title' => 'Managing Variations: Size and Color for Fashion Retail',
                'keywords' => 'fashion POS, variations, apparel inventory',
                'excerpt' => 'Apparel lives and dies by the size and colour matrix. Treating variants as first-class products prevents oversells.',
                'sections' => [
                    ['', 'A single shirt design might be 30 sellable items once you account for sizes and colours. Lump them together and your stock numbers become fiction.'],
                    ['Variants as real products', 'Each size-and-colour combination should have its own barcode and its own stock count. That is the only way to know that you are out of “Medium / Navy” but still have “Large / Navy”.'],
                    ['What good variant handling enables', [
                        'Accurate stock by variant, so you never oversell.',
                        'Faster, scan-based selling at the till.',
                        'Insight into which styles, sizes and colours actually sell.',
                    ]],
                    ['NoovaPOS Fashion', 'NoovaPOS manages size and colour matrices as proper variants, each barcoded and tracked, so your inventory stays exact across the range.'],
                ],
            ],
            [
                'slug' => 'distribution-route-management',
                'title' => 'Distribution Businesses: Route Management Best Practices',
                'keywords' => 'distribution POS, route management, delivery',
                'excerpt' => 'Distribution is logistics at scale. Smart route management cuts fuel, time and missed deliveries.',
                'sections' => [
                    ['', 'When you supply dozens or hundreds of customers, the difference between a good day and a bad one is how well your routes are planned.'],
                    ['Principles of good routing', [
                        'Group customers geographically into routes.',
                        'Assign each route to a driver and vehicle.',
                        'Balance load across days to avoid bottlenecks.',
                    ]],
                    ['Keep stock honest on the move', 'Stock transfers between the warehouse and vans need a clear audit trail, so you always know what is on each vehicle and what has been delivered.'],
                    ['NoovaPOS Distribution', 'NoovaPOS combines route management, delivery scheduling and stock transfers, giving you visibility from warehouse to customer.'],
                ],
            ],
            [
                'slug' => 'securing-your-pos',
                'title' => 'Securing Your POS: Roles, Permissions and Audit Logs',
                'keywords' => 'POS security, permissions, audit logs',
                'excerpt' => 'Your POS holds money, stock and customer data. A few security basics protect all three.',
                'sections' => [
                    ['', 'Most POS losses are not dramatic hacks — they are everyday gaps: shared logins, no permissions, no record of who did what.'],
                    ['Start with roles', 'Give each person the access their job needs and nothing more. A cashier does not need to edit prices; a manager does not need server access.'],
                    ['Build in accountability', [
                        'Unique logins for every user.',
                        'Permission checks on sensitive actions.',
                        'Audit logs that record who changed what, and when.',
                    ]],
                    ['Security in NoovaPOS', 'NoovaPOS offers dynamic, module-level permissions, shop and store restrictions, and audit logging, with tenant isolation keeping each business’s data separate.'],
                ],
            ],
            [
                'slug' => 'hidden-costs-of-manual-invoicing',
                'title' => 'The Hidden Costs of Manual Invoicing',
                'keywords' => 'invoicing, automation, billing',
                'excerpt' => 'Manual invoices feel free. They are not — they cost time, accuracy and cash flow.',
                'sections' => [
                    ['', 'Writing invoices by hand seems harmless until you add up the time, the errors and the slow payments it quietly causes.'],
                    ['Where it hurts', [
                        'Hours spent typing the same details repeatedly.',
                        'Mistakes that lead to disputes and rework.',
                        'Late invoices that delay payment and strain cash flow.',
                    ]],
                    ['Automation pays for itself', 'When invoices generate from the sale or the delivery automatically — including recurring ones — they go out on time, every time, with consistent numbering.'],
                    ['NoovaPOS billing', 'NoovaPOS generates invoices automatically, including recurring invoices for subscriptions and water delivery, and tracks overdue accounts for you.'],
                ],
            ],
            [
                'slug' => 'prepare-store-for-holiday-rush',
                'title' => 'Preparing Your Store for the Holiday Rush',
                'keywords' => 'holiday retail, peak season, POS',
                'excerpt' => 'Peak season rewards the prepared and punishes the rest. Here is a practical checklist to get ready.',
                'sections' => [
                    ['', 'The busiest weeks of the year are when systems and staff are tested hardest. A little preparation goes a long way.'],
                    ['Before the rush', [
                        'Stock up on best-sellers and set reorder alerts.',
                        'Train seasonal staff on fast checkout and shortcuts.',
                        'Make sure your POS works offline if the network struggles.',
                    ]],
                    ['During the rush', 'Keep an eye on real-time stock and daily takings so you can reorder fast-movers and spot problems early instead of after the weekend.'],
                    ['How NoovaPOS helps', 'With offline-first checkout, fast barcode billing and live reporting, NoovaPOS is built to stay quick and reliable exactly when volume spikes.'],
                ],
            ],
            [
                'slug' => 'cloud-vs-on-premise-pos',
                'title' => 'Cloud vs On-Premise POS: What’s Right for You?',
                'keywords' => 'cloud POS, on-premise, deployment',
                'excerpt' => 'Cloud or on-premise? The honest answer is that a modern POS should give you the best of both.',
                'sections' => [
                    ['', 'The cloud-versus-on-premise debate often misses the point. What matters is reliability at the till and access to your data anywhere.'],
                    ['The trade-offs', [
                        'Cloud: access anywhere, automatic updates, easy multi-store.',
                        'On-premise: full local control, but more to maintain.',
                        'Offline-first: cloud convenience that keeps selling without internet.',
                    ]],
                    ['Why offline-first bridges the gap', 'An offline-first cloud POS syncs to the cloud for reporting and multi-store, but keeps the local till running even when the connection does not.'],
                    ['The NoovaPOS model', 'NoovaPOS is cloud-based and multi-tenant, with offline-first POS and background sync — so you get cloud benefits without cloud fragility.'],
                ],
            ],
            [
                'slug' => 'bakery-production-planning',
                'title' => 'Bakery Production Planning with POS Data',
                'keywords' => 'bakery POS, production planning, sales data',
                'excerpt' => 'Bake too little and you sell out early; bake too much and you bin profit. Your POS data is the answer.',
                'sections' => [
                    ['', 'A bakery’s margin is decided before the doors open — in how much you choose to bake. Guesswork is expensive in both directions.'],
                    ['Let yesterday inform today', 'Sales history tells you what sold, when it sold out, and what went unsold. Use it to plan production by day of week and season.'],
                    ['Tie production to stock', [
                        'Record daily production so the counter reflects what is fresh.',
                        'Track ingredients so you reorder before you run short.',
                        'Watch best-sellers to plan tomorrow’s batch.',
                    ]],
                    ['NoovaPOS Bakery', 'NoovaPOS pairs a fast counter with production tracking and sales insights, so you bake to demand instead of to habit.'],
                ],
            ],
            [
                'slug' => 'customer-loyalty-regulars',
                'title' => 'Customer Loyalty: Turning One-Time Buyers into Regulars',
                'keywords' => 'customer loyalty, CRM, retention',
                'excerpt' => 'Winning a new customer costs far more than keeping one. Small, consistent touches turn buyers into regulars.',
                'sections' => [
                    ['', 'Acquisition gets the attention, but retention pays the bills. A returning customer is cheaper to serve and more profitable over time.'],
                    ['What builds loyalty', [
                        'Knowing your customers and their history.',
                        'Fast, friendly checkout that respects their time.',
                        'Relevant follow-ups rather than generic spam.',
                    ]],
                    ['Use the data you already have', 'Every sale is a signal. Customer records, purchase history and simple segmentation let you reward regulars and re-engage lapsed buyers.'],
                    ['NoovaPOS and your customers', 'NoovaPOS keeps customer profiles and history close to the point of sale, and its CRM add-on helps you turn one-time buyers into loyal regulars.'],
                ],
            ],
        ];
    }
}
