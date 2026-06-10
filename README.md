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

## Quick Start — Full Setup & Run

Complete process to bring everything up from a fresh clone (Windows + Laragon
shown; macOS/Linux identical commands).

### 0. Requirements
PHP 8.2+, Composer, Node 18+, MySQL 8, Redis. On Laragon: enable **MySQL** and
**Redis** from the menu, and turn on **Auto virtual hosts** for `*.local`.

### 1. Start services
Start **MySQL** and **Redis** in Laragon (Redis is required — queues, Horizon,
caching and tenant-DB provisioning use it).

### 2. Backend dependencies + environment
```bash
composer install
composer require laravel/horizon          # queue dashboard (first time only)
php artisan horizon:publish               # publishes Horizon assets
copy .env.example .env                     # macOS/Linux: cp .env.example .env
php artisan key:generate
```
Then edit `.env`:
```
APP_URL=http://noovapos.local
DB_DATABASE=noovapos        DB_USERNAME=root        DB_PASSWORD=
REDIS_HOST=127.0.0.1        REDIS_PORT=6379
QUEUE_CONNECTION=redis      CACHE_DRIVER=redis
TENANCY_CENTRAL_DOMAINS=noovapos.local
```

### 3. Database — create schema + seed everything
```bash
php artisan migrate:fresh --seed          # all tables incl. attendance + seed data
php artisan storage:link                  # for uploaded payment proofs
```
`--seed` runs roles/permissions (incl. `attendance.*`), plans, shop types, role
templates, platform/billing defaults, CMS content, and the demo tenant.
> Use `php artisan migrate` (without `:fresh`) to apply new migrations while
> keeping existing data.

### 4. Frontend — assets + build
```bash
npm install
npm run fetch:face-models                 # one-time: face-recognition weights (needs internet)
npm run build                             # production build  (or: npm run dev)
```
`fetch:face-models` makes attendance face recognition run fully self-hosted; the
scanner + face JS libs are already vendored in `public/vendor/`.

### 5. Queue worker (REQUIRED)
Background work — FBR sync, imports, **tenant-database provisioning**,
notifications — runs on the queue. Keep one running:
```bash
php artisan horizon                        # dashboard at /horizon (super admin only)
```
> On a fresh `migrate:fresh`, tenant databases are provisioned via the queue, so
> Horizon (or `php artisan queue:work redis`) **must** be running.

### 6. Hosts / wildcard domains
Add to `C:\Windows\System32\drivers\etc\hosts` (Laragon auto-vhosts may handle
`*.local` for you):
```
127.0.0.1  noovapos.local
127.0.0.1  abcgroup.noovapos.local
```

### 7. Open the app
- Platform super admin: `http://noovapos.local` → **superadmin@noovapos-gls.com / 123456**
- Demo tenant: `http://abcgroup.noovapos.local` → **tenant_owner@abcgroup.com / 123456**

(Every seeded account uses password `123456` — see **Demo Users** below.)

### One-shot rerun
```bash
composer install && php artisan migrate:fresh --seed && php artisan storage:link && npm install && npm run fetch:face-models && npm run build && php artisan horizon
```

> **HTTPS note:** WebAuthn (device-sensor biometric attendance) and camera access
> require a secure context — they work on `localhost`, otherwise enable SSL for
> the domain in Laragon.

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

`platform_super_admin`, `admin`, `tenant_owner`, `branch_manager`,
`shop_manager`, `cashier`, `accountant`, `inventory_manager`, `waiter`,
`kitchen`, `delivery_staff`, `delivery_boy`.

Restaurant back-of-house is covered by **`kitchen`** (KOT queue / kitchen
display, read-only on sales) and **`delivery_boy`** (the rider who fulfils
takeaway/delivery orders — sees assigned deliveries + their customers).
`delivery_staff` is the water/distribution driver. All are seeded with
permissions by `DefaultRoleSeeder`, and restaurant/water/distribution shop types
get matching **role templates** (`waiter`, `kitchen`, `delivery_boy`) via
`RoleTemplateSeeder`.

---

## SaaS Platform, Billing & Subscriptions

The platform super admin operates NoovaPOS itself; tenants operate their own
shops. System configuration is centralized to the super admin.

### Super admin (central, `noovapos.local`)
- **Platform → Tenants** — one row per tenant (stores nested), per-tenant
  **Separate DB** toggle, and **Assign Plan** with billing cycle + duration
  (months/years).
- **Plans** — limits, features, **Allowed Countries** (country-based visibility),
  and an **isolated-database** option (separate price + user allowance).
- **Roles & Permissions** — role+permission **templates per shop type**
  (permissions are super-admin-only; tenants instantiate roles from templates).
- **Subscription Payments** — every tenant payment (subscription / add-on / FBR)
  with the uploaded **proof image**; Confirm/Reject. Confirming activates the
  subscription / raises limits / enables FBR.
- **Bank Accounts** — payout accounts per country (shown to tenants by country).
- **Billing Settings** — long-term discount %, add-on per-unit rates, and FBR
  price — each **global or overridden per country**.
- **Shop Types** — enable/disable the business types tenants may pick.
- **System Settings** — Currencies, Languages, Payment Methods, Templates,
  Settings, Dual Screen (centralized; removed from tenants).
- **Offline & Sync**, **FBR (Pakistan)**, **Notifications**.

### Tenant (`*.noovapos.local`)
- **Billing & Plan** (`/app/billing`) — current plan, **trial countdown**,
  usage vs limits (registers are a **monthly** quota, reset each month), where to
  pay (country bank accounts), and a **payment request with required proof**.
  Buy **add-ons** (extra shops/users/products) and, for **Pakistan tenants
  only**, **enable/extend FBR**. Long-term discount auto-applies at the threshold.
- **Hard lock** — when the trial/subscription expires, every page redirects to
  Billing until the tenant renews.
- Plans are filtered to the tenant's **country**.

### Hierarchy & enforcement
```
Tenant → Store (has a business type) → Shop (inherits & locks the store's type)
       → User (assigned to stores + shops + a role)
```
Plan limits (stores/shops/registers/users/products, plus purchased add-ons and
separate-DB user allowance) are enforced on create via the subscription service.

### Hybrid database-per-tenant
Central DB by default (row-level `tenant_id`). Flip `TENANCY_DB_SWITCH=true` and
toggle **Separate DB** per tenant to run flagged tenants on their own database
(`ConditionalBootstrapTenancy`). See `database/migrations/tenant/` docs and run
`php artisan tenancy:create-databases` to provision flagged tenants.

### Seeding (central)
`php artisan db:seed` runs, in order: roles → permissions → super admin →
platform roles → settings → plans → **role templates** → **shop types** →
**platform defaults** (billing settings + bank accounts) → demo tenant. The demo
tenant (`HierarchyDemoSeeder`) is a **Pakistan** tenant exercising all shop
types, stores with types, shops, and users-by-role/shop.

---

## Extended modules (recent build)

Beyond the base SaaS/billing/hierarchy, these modules are now in place
(backend + admin/config UI; the marketing CMS and customer kiosk are public too):

- **Feature flags** — global (super admin: Platform → Features) ∧ per-store
  (tenant: Settings → Store Features); resolved via `FeatureService`.
- **Price tiers** — Retail/Wholesale/M20…; per-product tier prices; customer
  default tier (Settings → Price Tiers). POS has a **price-tier selector** in the
  cart that re-prices lines (defaults to the customer's tier).
- **Unit hierarchy** — per-product box/bar/pack/piece with `factor_to_base`
  (on the product form). POS has a **Mixed Units** action (gated by the
  `unit_hierarchy` feature): enter box/pack/piece counts → converts to a base
  quantity + stores `unit_breakdown` on the sale line
  (`GET /products/{id}/unit-levels`).
- **Deals / combos** — Inventory → Deals/Combos. POS has an **Add Deal** action
  (gated by `deals`) that expands a combo into real product lines priced to sum
  to the deal price (sale-save path unchanged).
- **Subscription checkout** — alongside the manual "request" (proof upload)
  method, tenants can **Pay online** via configurable channels: a global hosted
  **payment link**, or Pakistan locals (**JazzCash, Easypaisa, HBL, Meezan,
  UBL**, …). Super admin enables/disables each method and edits per-country
  channels under **Platform → Billing Settings → Payment methods**. Hosted pay
  page at `/billing/pay/{token}`.
- **Restaurant** — halls, tables (with seats), **kitchens** + `kitchen` role,
  KOT routing to the waiter's/hall's kitchen, **Send to Kitchen** + KOT print on
  POS, kitchen display.
  POS **Send to Kitchen** is gated by the `kitchen` feature and sends only the
  **delta** (newly added quantity) on each press — a waiter can hold a bill, add
  items, and reprint a KOT for just the new items.
- **Delivery** — `delivery_boy` role + Sales → Deliveries board (assign + track).
- **Customer kiosk** — `customer_displays` (token), public no-login page at
  `/kiosk/{token}`, orders route a KOT to the display's kitchen and land on the
  Customer Orders board (accept → assign waiter → served).
- **Marketing CMS** — Platform → CMS Pages / Blog; public pages at `/p/{slug}`,
  `/blog`, `/blog/{slug}` with SEO + per-country section visibility; the landing
  top-menu is dynamic (shop-type pages + FBR + Blog).

### Rerun the whole project (parent commands)

```bash
composer install
composer require laravel/horizon
php artisan horizon:publish
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
npm install
npm run build
php artisan horizon
```

Notes: `QUEUE_CONNECTION=redis` (set) and Redis must be running, or queued work
(FBR sync, imports, tenant-DB creation, notifications) won't process. `php artisan
horizon` is the single worker for all queues (default / fbr / notifications /
imports — see `config/horizon.php`); its dashboard is at `/horizon`, restricted to
the platform super admin via `HorizonServiceProvider`. Use `migrate` instead of
`migrate:fresh` to keep existing data.

Re-seed specific pieces:
`php artisan db:seed --class=DefaultRoleSeeder` ·
`--class=ShopTypeSeeder` · `--class=RoleTemplateSeeder` ·
`--class=PlatformDefaultsSeeder` · `--class=CmsSeeder` ·
`--class=DefaultPlansSeeder` · `--class=AttendancePermissionSeeder`.

### Attendance Management (POS-integrated)

A full attendance + workforce module that reuses the existing HR `employees`
table and the POS theme. Sidebar: **Attendance** → Dashboard · Check In/Out ·
Live · Tasks · History · Face/Fingerprint Enrollment · Devices · Reports ·
Requests · Settings.

- **Kiosk** (`/app/attendance/checkin`) — big clock + method cards (Face,
  Fingerprint, Barcode, QR, Manual). Smart flow: first scan of the day checks in
  immediately; if already in, shows the action panel (tasks, break, check-out).
- **Face recognition** is real and device-free via **face-api.js** (camera,
  client-side 1:N match; only numeric descriptors stored). **Barcode/QR** use a
  camera scanner (`html5-qrcode`) that also accepts USB scanners. Both libraries
  are **self-hosted** in `public/vendor/` (no runtime CDN). The face model
  weights are fetched once with `npm run fetch:face-models` (needs internet that
  one time) into `public/vendor/face-api/models`; after that, face recognition
  works fully offline / behind a firewall. Override paths via the `window.`
  globals `FACEAPI_SRC` / `FACEAPI_MODEL_URL` / `HTML5_QRCODE_SRC` if needed.
- **Fingerprint** camera capture is a *visual record* (future-ready), not a
  match. Real fingerprint matching → connect a scanner via **Devices**.
- **No-code Device Connectors** (`/app/attendance/devices`) — register any
  external scanner / face terminal / cloud API by configuring its endpoint,
  auth and request/response field-mapping; the platform calls it generically.
  `run_on=server` (platform calls it) or `client` (kiosk calls a local
  `localhost` agent). No code change to add a device.
- **Tasks** (start/pause/resume/complete, office/personal), **Breaks**,
  **Dashboard** (cards + charts + live timeline), **Live board**, **Reports**
  (summary / productivity / performance), **Requests** (employee corrections →
  manager approve/reject), and **Configuration → Attendance** (all toggles +
  face/fingerprint matching mode).
- Permissions: `attendance.*` (view, dashboard, checkin/out, manual, edit,
  delete, task.*, face.*, fingerprint.*, device.*, request.*, report.*,
  settings.*) seeded by `AttendancePermissionSeeder`.

Run: `php artisan migrate && php artisan db:seed --class=AttendancePermissionSeeder`.

### POS cart — now wired

Price-tier selector, **Add Deal**, **Mixed Units**, feature-gated **Send to
Kitchen** with delta-KOT reprint, and online subscription **checkout** are all
in place. Each was verified per-file; run one **test sale** on a real build to
confirm end-to-end before relying on them in production, since the sale-save and
KOT paths can't be exercised in the dev sandbox.

> Note: the printed customer slip already shows the sale/invoice id. The **FBR
> invoice number** is assigned asynchronously by the FBR sync queue (Pakistan),
> so it appears on the record/reprint once the sale has synced, not on the
> first instant slip.

---

## Demo Users (seeded via `HierarchyDemoSeeder`)

All demo accounts share the password: **`123456`**

### Platform

| Role | Email | Password |
|------|-------|----------|
| Platform Super Admin | superadmin@noovapos-gls.com | 123456 |

### Tenant: ABC Group (`abcgroup.noovapos.local`)

| Role | Email | Password |
|------|-------|----------|
| Tenant Owner | tenant_owner@abcgroup.com | 123456 |
| Branch Manager (Main Branch) | branch_manager@abcgroup.com | 123456 |
| Branch Manager (City Branch) | branch_manager2@abcgroup.com | 123456 |

#### Main Branch — Retail Counter

| Role | Email | Password |
|------|-------|----------|
| Shop Manager | retail.manager@abcgroup.com | 123456 |
| Cashier | retail.cashier@abcgroup.com | 123456 |
| Inventory Manager | retail.inventory@abcgroup.com | 123456 |

#### Main Branch — Restaurant Hall

| Role | Email | Password |
|------|-------|----------|
| Shop Manager | restaurant.manager@abcgroup.com | 123456 |
| Waiter | waiter@abcgroup.com | 123456 |
| Kitchen | kitchen@abcgroup.com | 123456 |
| Delivery Boy | delivery.boy@abcgroup.com | 123456 |

#### Main Branch — Pharmacy Counter

| Role | Email | Password |
|------|-------|----------|
| Cashier | pharmacy.cashier@abcgroup.com | 123456 |
| Inventory Manager | pharmacy.inventory@abcgroup.com | 123456 |

#### Main Branch — Bakery Counter

| Role | Email | Password |
|------|-------|----------|
| Cashier | bakery.cashier@abcgroup.com | 123456 |

#### Main Branch — Fashion Store

| Role | Email | Password |
|------|-------|----------|
| Cashier | fashion.cashier@abcgroup.com | 123456 |

#### City Branch — Water Supply Center

| Role | Email | Password |
|------|-------|----------|
| Shop Manager | water.manager@abcgroup.com | 123456 |
| Delivery Staff | water.delivery@abcgroup.com | 123456 |

#### City Branch — Electronics Store

| Role | Email | Password |
|------|-------|----------|
| Cashier | electronics.cashier@abcgroup.com | 123456 |
| Inventory Manager | electronics.inventory@abcgroup.com | 123456 |

#### City Branch — Distribution Hub

| Role | Email | Password |
|------|-------|----------|
| Delivery Staff | distribution.driver@abcgroup.com | 123456 |
| Inventory Manager | distribution.inventory@abcgroup.com | 123456 |

#### City Branch — Monthly Services

| Role | Email | Password |
|------|-------|----------|
| Shop Manager | service.manager@abcgroup.com | 123456 |
| Accountant | service.accountant@abcgroup.com | 123456 |

#### City Branch — Custom Business

| Role | Email | Password |
|------|-------|----------|
| Cashier | custom.cashier@abcgroup.com | 123456 |

---

Tenants register at `/register-tenant`.

---

## Login & Domain Setup

### How the login URL works

| Who | URL | Notes |
|-----|-----|-------|
| Super Admin | `http://noovapos.local/login` | Central domain — no subdomain |
| Tenant Owner / Staff | `http://abcgroup.noovapos.local/login` | Subdomain identifies the tenant |

### Common login error: "Hostname does not include a subdomain"

**Cause:** Stancl Tenancy's subdomain middleware was running on the central domain (`noovapos.local`) and throwing before the login controller was reached.

**Fix applied:** `App\Http\Middleware\InitializeTenancyForApi` now wraps the Stancl middleware. It detects central domains and passes the request through without tenancy initialization. Tenant subdomains still go through normal tenancy bootstrapping.

If you still see the error after pulling, run:

```bash
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
```

### Architecture: single central DB (not separate DB per tenant)

NoovaPOS uses **one central MySQL database** (`noovapos`) with `tenant_id` row-level scoping via the `Multitenantable` trait. There is **no separate database per tenant**.

Stancl Tenancy is used only for **subdomain identification** — it reads `abcgroup.noovapos.local` → looks up the `abcgroup` domain in the `domains` table → sets `tenancy()->tenant` so the app knows which tenant is active. The database is never switched.

`DatabaseTenancyBootstrapper` is disabled in `config/tenancy.php` for this reason.

---

### Laragon setup (Windows)

**Step 1 — Hosts file** (`C:\Windows\System32\drivers\etc\hosts`, open as Administrator):

```
127.0.0.1   noovapos.local
127.0.0.1   superadmin.noovapos.local
127.0.0.1   abcgroup.noovapos.local
```

Add one line per tenant subdomain you need to test. Laragon does not support wildcard DNS by default — each subdomain must be listed explicitly.

**Step 2 — Laragon wildcard virtual host**

Laragon auto-creates a vhost for `noovapos.local` but not for `*.noovapos.local`. You need to add a wildcard vhost manually.

Open: `C:\laragon\etc\nginx\sites-enabled\noovapos.local.conf` (or create it)

Add a second server block for the wildcard:

```nginx
# Wildcard: handles abcgroup.noovapos.local, anyother.noovapos.local, etc.
server {
    listen 80;
    server_name *.noovapos.local;
    root "D:/laragon/www/noovapos/public";

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php_upstream;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Then **restart Laragon** (right-click tray icon → Reload / Restart All).

**Step 3 — Verify**

```bash
# Should return the Laravel app HTML
curl -H "Host: abcgroup.noovapos.local" http://127.0.0.1/
```

`.env` must have:

```env
APP_URL=http://noovapos.local
CENTRAL_DOMAIN=noovapos.local
TENANT_BASE_DOMAIN=noovapos.local
```

### Common login error: "CSRF token mismatch"

**Cause:** `EnsureFrontendRequestsAreStateful` was in the API middleware group and `noovapos.local` was listed in `SANCTUM_STATEFUL_DOMAINS`. Sanctum activated cookie/CSRF mode for every request from the central domain, then rejected the login POST because no CSRF cookie was fetched first.

**Fix applied:** `EnsureFrontendRequestsAreStateful` removed from `Kernel.php` api group. This app uses **Bearer token auth** (token stored in a JS cookie, sent as `Authorization: Bearer …`), not Sanctum's stateful SPA mode. CSRF protection is unnecessary and actively harmful here. `SANCTUM_STATEFUL_DOMAINS` reduced to `localhost,127.0.0.1`.

---

### Why the login API had no network call before

`environment.js` was hardcoding `:8000` for `localhost` requests, pointing axios at the wrong port (Laragon uses port 80). It now reads `window.location.port` directly, so it works on Laragon (port 80 → no suffix), `php artisan serve` (port 8000), and production (port 443).

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