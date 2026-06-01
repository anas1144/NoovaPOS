# NoovaPOS  — Multi-Tenant SaaS POS + ERP

Enterprise-grade SaaS POS + ERP foundation built per the specification in
`CLAUDE.md` and `info.text`. One platform supports multiple business types
(retail, restaurant, pharmacy, water supply, distribution, …) with isolated
tenants, per-shop module activation, and a complete platform super-admin.

```
Platform Super Admin
    → Tenants / Companies
        → Stores / Branches
            → Shops / POS Counters / Registers
                → Users / Employees
```

---

## Status — Audit Against CLAUDE.md (30-point spec)

Legend: ✅ shipped · 🟡 partial / scaffolded · ⛔ not started

| # | Spec item | Status | Notes |
|---|-----------|--------|-------|
| 1 | Complete system architecture | ✅ | Tenants → Stores → Shops → Registers → Users; modular per-shop business types |
| 2 | SaaS tenancy architecture | ✅ | Stancl Tenancy, subdomain + domain table, tenant onboarding service, isolation |
| 3 | Database schema | ✅ | 60+ migrations; SaaS, restaurant, recurring, accounting, HR, CRM, notifications, FBR, sync |
| 4 | Laravel folder structure | ✅ | `Models / Http/Controllers/API / Services / Repositories / Resources / Traits` |
| 5 | React/Vue folder structure | ✅ | React 17, Redux, per-module folders under `resources/pos/src/components/*` |
| 6 | API architecture | ✅ | Platform / tenant / POS / public / offline-sync APIs split by middleware |
| 7 | Queue architecture | 🟡 | FBR + Notifications dispatchers run in-process; Horizon hooks ready, not deployed |
| 8 | Offline sync architecture | ✅ | `offline_devices`, `sync_batches`, `sync_queue`, idempotent `local_uuid` |
| 9 | POS optimization strategy | ✅ | `VirtualizedProductGrid` (react-window `FixedSizeGrid` + AutoSizer) auto-activates for catalogs > 200 SKUs; smaller shops keep the original flex-wrap layout |
| 10 | Enterprise import system | ✅ | Existing `import_jobs / items` engine retained for products, customers, suppliers |
| 11 | Inventory architecture | ✅ | Event-style `stock_movements` ledger + per-warehouse stock + adjustments |
| 12 | Accounting architecture | ✅ | Chart of accounts, balanced JEs, posting, trial balance, P&L, balance sheet, auto-journal |
| 13 | Subscription engine | ✅ | Plans (cycle, limits, feature flags), Subscriptions (trial/active/etc.), assign-plan, suspend |
| 14 | Delivery scheduling engine | ✅ | Customer subscriptions with `schedule_days`, auto-generates 30 days of deliveries, route + driver |
| 15 | Restaurant workflow | ✅ | Halls, tables (state machine), waiter assignment, KOT board (open→sent→cooking→ready→served) |
| 16 | Water supply workflow | ✅ | Bottle deposits, extra-bottle requests, returned bottles, recurring invoices |
| 17 | FBR integration workflow | ✅ | Profiles (NTN/STRN/POS ID/sandbox/production tokens), `fbr_invoices` queue, submission service, QR payload |
| 18 | Redis caching strategy | ✅ | `TenantCacheService` (tagged per-tenant cache, TTL tiers); wired into `SettingAPIController` (all 4 endpoint groups, flushed on every write) and `DashboardAPIController` (today + range KPIs) |
| 19 | Meilisearch strategy | 🟡 | `MeilisearchProduct` trait added to `Product` model (plug-and-play once `laravel/scout` + Meilisearch driver are installed); runtime search still uses Eloquent LIKE |
| 20 | UI/UX structure | ✅ | Existing dense data-grid theme reused; new modules follow same layout |
| 21 | CSS design system | ✅ | `design-tokens.scss` with CSS custom properties for colours, spacing, elevation, radius; dark-mode toggle in `utils/theme.js`; imported in global `style.react.scss` |
| 22 | Server scaling strategy | 🟡 | Stateless APIs + queue services in place; load balancer / autoscaler not provided |
| 23 | Security architecture | ✅ | Sanctum auth, tenant isolation, permission middleware, rate-limit on auth, audit logs |
| 24 | Queue worker strategy | ✅ | `config/horizon.php` queue groups (default, fbr, notifications, imports); `docker/supervisor/supervisord.conf` for 4 workers per queue on production |
| 25 | Backup strategy | ✅ | `tenant_backups` table (queue → run → complete → download) with status updates |
| 26 | Audit logging system | ✅ | `audit_logs` with actor, IP, UA, old/new values; platform UI page |
| 27 | Notification architecture | ✅ | Outbox + dispatcher; channels: in_app, email (Laravel Mail), sms/whatsapp/push (driver stubs) |
| 28 | AI architecture | ✅ | Pure-SQL insights: sales forecast (weighted MA + slope), reorder suggestions, customer trends |
| 29 | Deployment architecture | ✅ | `Dockerfile` (PHP 8.2-fpm), `docker/nginx/default.conf`, `docker/supervisor/supervisord.conf`, `docker-compose.prod.yml` (app + mysql + redis + nginx + horizon); SSL via Cloudflare proxy |
| 30 | DevOps strategy | ✅ | `.github/workflows/ci.yml` (lint → test → build → push Docker image); migrations + seeders; `docker-compose.prod.yml` for one-command production deploy |

Foundational POS / ERP features (sales, purchases, returns, holds, quotations,
payments, registers, warehouses, transfers, adjustments, reports, settings,
mail/SMS templates, multi-language) all retained and working.

---

## Shipped Modules

### Platform (Super Admin)
Dashboard (tenant, subscription, backup health stats), Tenants (onboard,
suspend, assign plan), Plans CRUD with feature-flag matrix, Subscriptions,
Audit Logs, Tenant Backups (queue/run/download).

### Tenant Workspace
- Stores / Branches with active-store switcher.
- Shops / Counters typed by `shop_type` (retail, restaurant, pharmacy,
  water_supply, bakery, electronics, fashion, distribution, monthly_service,
  custom) plus `enabled_modules`.
- POS Registers (open/close, cash drawer, register reports).
- Products, Variations, Categories, Brands, Units, Base Units.
- Sales (multi-payment, split, credit, returns, holds, quotations).
- Purchases, Purchase Returns, Suppliers.
- Customers + best/all customer reports.
- Inventory: Warehouses, Transfers, Adjustments, **Stock Movements ledger**.
- Expenses & Expense Categories.
- Reports: Sales, Purchases, Stock, Stock Movements, Profit/Loss, Top Selling,
  Supplier, Customer, Register, Warehouse.
- Settings: General, Prefixes, Mail, SMS, Receipt, Taxes, Payment Methods,
  POS, Dual-screen.

### Restaurant Module
- Halls, Tables (state machine: free→occupied→reserved→billed).
- KOT Kanban board (open→sent→cooking→ready→served), auto-refresh 15 s.
- Order types: dine_in, takeaway, delivery.

### Recurring / Water-Supply Module
- Recurring Plans (cycles + bottle deposit).
- Customer Subscriptions (day-of-week schedule, route, bottles-with-customer).
- Delivery schedule auto-generated for 30 days on subscription create.
- Recurring Invoices generated from delivered rows in a period.

### Accounting Module
- Chart of Accounts (5 types with parent hierarchy).
- Balanced Journal Entries (draft → posted).
- Trial Balance, Profit & Loss, Balance Sheet with running totals.
- Auto-Journal service for sale / purchase / expense (idempotent posting,
  default COA seeding on first run).

### HR Module
- Employees (code, designation, department, employment type, salary, cycle).
- Attendance (check-in/out, hours, statuses: present/late/half_day/leave/absent).
- Per-employee quick check-in / check-out buttons; per-day status board.

### CRM Module
- Leads with stages (new / contacted / qualified / proposal / won / lost).
- Kanban pipeline view with drag-stage dropdown per card.
- Pipeline stats: open leads, pipeline value, won, win rate.
- Public `demo-request` from the pricing page lands here.

### FBR (Pakistan)
- Per-tenant or per-store profile.
- `fbr_invoices` queue with statuses: queued / sending / synced / failed /
  retrying. Retry + bulk "Process queue" button.
- Sandbox stub generates plausible invoice no + QR payload so the receipt
  flow works without a licensed integrator.
- Real-integrator hook: set `FBR_API_ENDPOINT` env → service posts payload
  with the profile's token.

### Offline POS
- `offline_devices` heartbeat + register endpoint.
- `sync_batches` and `sync_queue` with idempotent `local_uuid`.
- React UI for devices list + sync queue with retry / mark-synced.
- **PWA**: `manifest.json` (shortcuts, maskable icons), `service-worker.js`
  (network-first HTML, stale-while-revalidate static, API GET cache, sync
  event hook for `offline-sales`).

### AI Insights
- Sales forecast: history + 7-day weighted MA + 14-day least-squares slope,
  N-day projection.
- Reorder suggestions: daily velocity (last N days), current stock, days of
  cover, suggested qty, urgency (critical/high/medium/low).
- Customer trends: top customers by revenue / orders / avg ticket.

### Notifications
- Channels: in_app, email, sms, whatsapp, push.
- Outbox with statuses queued → sending → sent/failed/read.
- Dispatcher with pluggable drivers (Twilio / WhatsApp / FCM **left as stubs
  per request**; email uses Laravel `Mail::raw`).
- Per-user unread badge, mark-all-read, manual "Send queued" trigger.

### Public Marketing
- `/pricing` landing page: hero, business-type cards, monthly/yearly
  toggle, plans pulled from `GET /api/public/plans`, demo-request modal
  posting to `POST /api/public/demo-request`.
- `/register-tenant` self-onboarding screen → creates tenant + default
  store + shop + owner.

---

## Tech Stack

| Layer | Stack |
|-------|-------|
| Backend | Laravel (PHP 8+), Sanctum, Stancl Tenancy |
| Database | MySQL 8 (Docker option provided) |
| Frontend | React 17, Redux, React Router, React-Bootstrap, FontAwesome |
| Build | Laravel Mix / Webpack |
| Queues | Laravel Queues, Redis-ready (Horizon/Supervisor not bundled) |
| PWA | manifest.json + custom service worker |
| Reports | Excel exports, PDF receipts |

---

## Roles (seeded)

`platform_super_admin`, `tenant_owner`, `branch_manager`, `store_manager`,
`shop_manager`, `cashier`, `accountant`, `waiter`, `delivery_staff`,
`inventory_manager`.

Default platform login (seeded):

```
superadmin@noovapos-gls.com / 123456
```

Tenants register at `/register-tenant`.

---

## Key API Surface

| Area | Routes |
|------|--------|
| Auth | `POST /api/login`, `POST /api/register-tenant`, `POST /api/logout` |
| Platform | `/platform/dashboard`, `/platform/tenants`, `/platform/plans`, `/platform/subscriptions`, `/platform/audit-logs`, `/platform/backups` |
| Tenant | CRUD: `/stores`, `/shops`, `/users`, `/products`, `/sales`, `/purchases`, `/customers`, … |
| FBR | `/fbr-profiles`, `/fbr-invoices`, `/fbr-invoices/{id}/submit`, `/fbr-invoices/{id}/retry`, `/fbr-invoices/process-queue` |
| Offline Sync | `/offline-devices`, `/offline-devices/register`, `/offline-devices/{id}/heartbeat`, `/offline-sync/batches`, `/sync-queue` |
| Stock | `/stock-movements`, `/stock-movements-summary` |
| Restaurant | `/restaurant/halls`, `/restaurant/tables`, `/restaurant/tables/{id}/state`, `/restaurant/kots`, `/restaurant/kots/{id}/status` |
| Recurring | `/recurring/plans`, `/recurring/customer-subscriptions`, `/recurring/customer-subscriptions/{id}/(pause\|resume\|generate-invoice)`, `/recurring/delivery-schedules`, `/recurring/invoices` |
| Accounting | `/accounting/accounts`, `/accounting/journal-entries`, `/accounting/journal-entries/{id}/post`, `/accounting/trial-balance`, `/accounting/profit-loss`, `/accounting/balance-sheet`, `/accounting/ledger/{account}` |
| Auto-Journal | `POST /accounting/seed-chart-of-accounts`, `/accounting/auto-post/(sale\|purchase\|expense)/{id}` |
| HR | `/hr/employees`, `/hr/attendance`, `/hr/employees/{id}/(check-in\|check-out)` |
| CRM | `/crm/leads`, `/crm/leads/{id}/stage`, `/crm/pipeline-stats` |
| Notifications | `/notifications`, `/notifications/unread-count`, `/notifications/{id}/read`, `/notifications/mark-all-read`, `/notifications/dispatch` |
| AI Insights | `/insights/sales-forecast`, `/insights/reorder-suggestions`, `/insights/customer-trends` |
| Public | `GET /public/plans`, `POST /public/demo-request` |

---

## Database — Tables added on top of base POS

```
tenants / domains             SaaS tenant root + custom & subdomain mapping
stores / shops                branches and counters (per-tenant, shop_type)
user_stores / user_shops      user ↔ branch / counter assignments
plans / subscriptions         SaaS plans + per-tenant subscription state
audit_logs                    tenant-scoped action log (event + diff)
tenant_backups                queued/running/completed/failed
stock_movements               in/out, type, reference (source of truth)
fbr_profiles                  per-tenant or per-store FBR config
fbr_invoices                  submission queue + QR payload + retries
offline_devices               registered POS terminals
sync_batches / sync_queue     offline → server idempotent batches
restaurant_halls / _tables    halls + tables with state
kitchen_order_tickets / _items KOT header + line items
recurring_plans               billable recurring services
customer_subscriptions        per-customer plan w/ schedule_days
delivery_schedules            per-day deliveries (qty + extras + returns)
recurring_invoices            generated period invoices
accounts                      chart of accounts
journal_entries / _lines      balanced JE header + lines
notifications_outbox          channel-agnostic notification queue
employees / attendances       HR module
crm_leads                     CRM pipeline leads
```

---

## Running Locally

### Database (Docker MySQL + phpMyAdmin)

```bash
docker compose up -d
docker compose logs -f mysql
docker compose down
```

phpMyAdmin: http://localhost:8080 · user `app` / pass `app_pass`

### Backend

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

### Frontend

```bash
npm install
npm run dev     # development
npm run watch   # auto-rebuild
npm run prod    # production
```

Visit http://localhost:8000 — login or sign up via `/register-tenant`,
public pricing at `/pricing`.

---

## Stock Movement Flow

Every business transaction now writes a row in `stock_movements`:

| Source | direction | type |
|--------|-----------|------|
| Purchase | in | `purchase` |
| Purchase Return | out | `purchase_return` |
| Sale | out | `sale` |
| Sale Return | in | `sale_return` |
| Transfer | in/out | `transfer` |
| Adjustment | in/out | `adjustment` |

---

## Roadmap — remaining items per CLAUDE.md

- ✅ **POS virtualized rendering** — `VirtualizedProductGrid` with react-window
  auto-activates for catalogs > 200 SKUs.
- ✅ **Redis caching strategy** — `TenantCacheService` wired into Settings +
  Dashboard controllers; per-tenant tagged cache with write-through flush.
- ✅ **CSS design system** — `design-tokens.scss` (variables for colours,
  spacing, elevation, radius) + dark-mode toggle.
- ✅ **Queue worker strategy** — Horizon config + Supervisor config bundled.
- ✅ **Production deployment / DevOps** — Dockerfile, Nginx, docker-compose.prod.yml,
  GitHub Actions CI/CD pipeline.
- 🟡 **Meilisearch** — `MeilisearchProduct` trait on `Product` model is ready;
  still needs `composer require laravel/scout` + `SCOUT_DRIVER=meilisearch` in
  `.env`, then `php artisan scout:import "App\Models\Product"`.
- ⛔ **Electron / native PWA POS app** — PWA shell shipped; native shell
  deliberately excluded.
- ⛔ **Real notification senders** — Twilio / WhatsApp Cloud API / FCM
  left as driver stubs per project owner request.
- ⛔ **Realtime WebSockets** — KOT board polls every 15 s; can be upgraded
  to Laravel Echo + Soketi when needed.
- ⛔ **FBR licensed integrator wiring** — hook (`FBR_API_ENDPOINT` env) in
  place; integrator credentials not bundled.

---

## License

Internal project — see project owner for license terms.



Here are all the commands — copy/paste them in sequence in your terminal from `D:\laragon\www\noovapos`:

---

## 🔴 Nuclear Reset — Wipe Everything & Start Fresh

```bash
# 1. Drop all central DB tables + re-run all migrations + re-seed
php artisan migrate:fresh --seed
```

That single command does everything for the **central database** (`noovapos`). It:
- Drops all tables
- Runs every migration in order
- Runs `DatabaseSeeder` → permissions → roles → superadmin → settings → plans → demo tenant

---

## 🗑️ Also Wipe Tenant Databases

Tenant databases (`tenantXXX`) are separate MySQL databases. To nuke those too:

```bash
# List all tenant databases first (so you know what to drop)
php artisan tinker --execute="
\App\Models\MultiTenant::all()->each(function(\$t) {
    echo \$t->id . ' → ' . \$t->database()->getName() . PHP_EOL;
});
"

# Drop all tenant DBs + their domain entries + tenants rows
php artisan tinker --execute="
\App\Models\MultiTenant::all()->each(function(\$t) {
    try {
        \$t->delete(); // fires TenantDeleted → deletes the DB automatically
        echo 'Deleted tenant: ' . \$t->id . PHP_EOL;
    } catch (\Throwable \$e) {
        echo 'Error: ' . \$e->getMessage() . PHP_EOL;
    }
});
"

# Now re-run fresh migration + seed on central DB
php artisan migrate:fresh --seed
```

---

## ✅ Soft Reset — Keep Structure, Re-Seed Only

```bash
# Just re-run seeders without dropping tables
php artisan db:seed
```

---

## 🏗️ Manually Create a Tenant (via Tinker)

```bash
php artisan tinker
```

Then inside tinker:

```php
// Create tenant + its database automatically
$service = app(\App\Services\TenantOnboardingService::class);

$result = $service->onboard([
    'business_name'  => 'Acme Retailers',
    'subdomain'      => 'acme',           // → acme.noovapos.local
    'first_name'     => 'Ahmed',
    'last_name'      => 'Malik',
    'email'          => 'ahmed@acme.com',
    'phone'          => '+923001234567',
    'password'       => 'password123',
]);

echo "Tenant ID: " . $result['tenant']->id . PHP_EOL;
echo "Domain: "    . $result['domain']      . PHP_EOL;
echo "DB Name: "   . $result['tenant']->database()->getName() . PHP_EOL;
```

---

## 🔄 Run/Retry Failed Jobs

```bash
# Process all queued jobs once (sync driver = runs immediately)
php artisan queue:work --once

# If using database queue driver — retry failed jobs
php artisan queue:retry all

# Clear failed jobs table
php artisan queue:flush

# Run the queue worker continuously (keep this running in background)
php artisan queue:work --tries=3 --timeout=90
```

---

## 🧹 Clear All Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Or all at once:
php artisan optimize:clear
```

---

## 🔑 Re-Generate App Key (if needed)

```bash
php artisan key:generate
```

---

## 📦 Full Fresh Install (after cloning / first setup)

```bash
# 1. Install PHP dependencies
composer install

# 2. Install JS dependencies
npm install

# 3. Copy env
cp .env.example .env

# 4. Generate app key
php artisan key:generate

# 5. Run migrations + seed
php artisan migrate:fresh --seed

# 6. Build frontend assets
npm run build

# 7. Clear everything
php artisan optimize:clear

# 8. Create storage symlink
php artisan storage:link
```

---

## 🔎 Useful Check Commands

```bash
# Check which tenants exist
php artisan tinker --execute="
\Stancl\Tenancy\Database\Models\Domain::with('tenant')->get()
    ->each(fn(\$d) => print(\$d->domain . ' → tenant: ' . \$d->tenant_id . PHP_EOL));
"

# Check superadmin user exists
php artisan tinker --execute="
\$u = \App\Models\User::where('email','superadmin@noovapos-gls.com')->first();
echo \$u ? 'EXISTS — role: ' . \$u->roles->pluck('name')->first() : 'NOT FOUND';
"

# Check all plans
php artisan tinker --execute="
\App\Models\Plan::all(['name','price','price_pkr','trial_days'])
    ->each(fn(\$p) => print(\$p->name . ' $' . \$p->price . ' | ₨' . \$p->price_pkr . PHP_EOL));
"

# Check central domains (tenancy config)
php artisan tinker --execute="
print_r(config('tenancy.central_domains'));
"

# Check queue status
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed
```

---

## 🚀 Quick Daily Workflow

```bash
# Start fresh every morning during dev
php artisan migrate:fresh --seed && php artisan optimize:clear && npm run dev
```