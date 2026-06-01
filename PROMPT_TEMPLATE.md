# NoovaPOS — New Chat Prompt Template

Copy everything between the `─── START ───` and `─── END ───` markers and paste it at the start of a new chat to give the AI full context.

---

─── START ───

## Project: NoovaPOS  — Multi-Tenant SaaS POS + ERP

**Location:** `D:\laragon\www\noovapos`

### Stack
- **Backend:** Laravel 11, PHP 8.2, MySQL 8, Redis, Stancl Tenancy 3, Sanctum 4, Spatie Permission 6
- **Frontend:** React 17, Redux, React Router 6, React-Bootstrap-v5, Tailwind-less (Bootstrap 5 + custom SCSS tokens)
- **Build:** Laravel Mix 6 + Webpack 5 (`npm run dev`), react-scripts 5 (`npm run build`)
- **Node:** 22.x — always use `npm install --legacy-peer-deps`
- **Infra:** Redis, Meilisearch (plug-and-play), Horizon, Supervisor, Docker, GitHub Actions CI/CD

### Hierarchy
```
Platform Super Admin
  → Tenants / Companies
      → Stores / Branches
          → Shops / POS Counters (shop_type: retail|restaurant|pharmacy|water_supply|bakery|…)
              → Registers
                  → Users / Employees
```

### Architecture conventions
| Area | Convention |
|---|---|
| Controllers | `app/Http/Controllers/API/*APIController.php` |
| Models | `app/Models/` — all use `Multitenantable` trait + `tenant_id` (uuid) |
| Services | `app/Services/` |
| Repositories | `app/Repositories/` |
| Routes | `routes/api.php` inside `auth:sanctum` + `tenant.active` group |
| Frontend pages | `resources/pos/src/components/<module>/` |
| Redux actions | `resources/pos/src/store/action/` |
| Redux reducers | `resources/pos/src/store/reducers/` (register in `index.js`) |
| API constants | `resources/pos/src/constants/index.js` |
| Routes | `resources/pos/src/routes.js` |
| Sidebar | `resources/pos/src/config/asideConfig.js` |
| Migrations | `database/migrations/` — dated `2026_05_*` for new work |
| UI components | `MasterLayout`, `ReactDataTable`, `react-bootstrap-v5` modals, `ActionButton`, `DeleteModel`, FontAwesome icons |

### What is FULLY built (✅)
1. Platform super-admin: dashboard, tenants (onboard/suspend/assign-plan), plans CRUD, subscriptions, audit logs, tenant backups
2. SaaS tenancy: subdomain + custom domain, tenant isolation, `tenant.active` middleware
3. Shops/Counters: `shop_type` + `enabled_modules` per shop
4. POS core: barcode scan, fast cart, multi-payment, split payment, holds, quotations, receipt print, QR invoices, keyboard shortcuts (F1-F8, Ctrl+Enter)
5. POS performance: `VirtualizedProductGrid` (react-window) auto-activates for >200 SKUs; flat-file + Redis cache for products
6. Offline POS: PWA manifest + service worker, `offline_devices`, `sync_batches`, `sync_queue`, idempotent `local_uuid`
7. FBR Pakistan: profiles (NTN/STRN/POS ID/tokens), `fbr_invoices` queue, retry, QR payload, sandbox stub
8. Restaurant module: halls, tables (state machine), KOT Kanban (open→sent→cooking→ready→served), waiter assignment
9. Water-supply/Recurring: recurring plans, customer subscriptions, delivery schedule auto-generation, recurring invoices
10. Accounting: chart of accounts, balanced JEs, trial balance, P&L, balance sheet, `AutoJournalService` (idempotent)
11. HR: employees + attendance (check-in/out)
12. CRM: leads + Kanban pipeline + stats
13. AI Insights: sales forecast (weighted MA + slope), reorder suggestions, customer trends (pure SQL, no external API)
14. Notifications: outbox + dispatcher (in_app/email working; SMS/WhatsApp/FCM = driver stubs)
15. Public marketing: `/pricing` landing page + `/register-tenant` self-onboarding
16. Redis caching: `TenantCacheService` (tagged per-tenant, TTL tiers) wired into Settings + Dashboard controllers
17. CSS design system: `design-tokens.scss` (CSS custom properties), dark-mode toggle
18. Docker + CI/CD: `Dockerfile`, `docker-compose.prod.yml`, Nginx, Supervisor, Horizon, GitHub Actions
19. Meilisearch: `MeilisearchProduct` trait on Product model (plug-and-play, needs `laravel/scout` install)

### Deliberately SKIPPED (per owner request)
- Real WhatsApp/SMS/FCM senders (Twilio etc.)
- Electron native app shell
- WebSockets / Laravel Echo / Soketi (KOT board uses 15s polling instead)
- FBR licensed-integrator credentials

### Key files to know
| File | Purpose |
|---|---|
| `CLAUDE.md` | Full feature spec (30-point) |
| `README.md` | Status audit table (30 rows) + shipped modules |
| `app/Services/TenantCacheService.php` | Per-tenant Redis cache helper |
| `app/Services/AutoJournalService.php` | Auto-posts sales/purchases/expenses to JEs |
| `app/Services/FbrSubmissionService.php` | FBR invoice submission + retry |
| `resources/pos/src/frontend/components/product/VirtualizedProductGrid.js` | react-window product grid |
| `resources/pos/src/App.js` | React root — routing, auth guard, language |
| `update.bat` / `update.sh` | One-command update script |

### How to run
```bash
# Backend
php artisan migrate
php artisan db:seed
php artisan serve

# Frontend
npm install --legacy-peer-deps
npm run dev

# One-command update (Windows/Laragon)
update.bat
```

### Default login
```
URL:      http://127.0.0.1:8000
Admin:    superadmin@noovapos.com  /  123456
Register: http://127.0.0.1:8000/#/register-tenant
Pricing:  http://127.0.0.1:8000/#/pricing
```

### Important notes for new work
- `routes.js` ends with `];` — append new route objects before it
- `asideConfig.js` — append new sidebar items before the closing `]`
- `constants/index.js` — add new action type constants here
- Always verify FontAwesome icon names exist in `node_modules/@fortawesome/free-solid-svg-icons` before using
- New migrations: always use `firstOrCreate` or `whereNotExists` guards — migrations run on existing DBs
- Spatie permissions: never use `$role->hasPermissionTo()` in seeders/migrations — it throws if permission doesn't exist; use `$role->permissions->contains('name', $perm)` instead

─── END ───

---

## Quick tips for common tasks

### Add a new module (e.g. "Inventory Alerts")
1. Migration → Model → Controller → Routes → Redux action/reducer → React page → sidebar entry → routes.js
2. Table needs `tenant_id` + `Multitenantable` trait
3. Add `manage_inventory_alerts` to `DefaultPermissionsSeeder`
4. Add to `GenerateCrudPermissionsSeeder`'s `$modules` array

### Add a new permission
1. Add to `DefaultPermissionsSeeder::$permissions`
2. Add `manage_*` to `AssignAllPermissionAdminRole` if it should be admin-default
3. Run `php artisan db:seed --class=DefaultPermissionsSeeder --force`
4. Run `php artisan permission:cache-reset`

### Fix "permission does not exist" migration error
- Check `GenerateCrudPermissionsSeeder` — use `$role->permissions->contains('name', $perm)` not `hasPermissionTo()`
- Create a migration before the CRUD seeder migration to seed missing `manage_*` permissions

### Fix webpack / Node build errors
```bash
# Delete broken webpack and reinstall
rmdir /s /q node_modules\webpack
npm install --legacy-peer-deps
npm run dev
```
