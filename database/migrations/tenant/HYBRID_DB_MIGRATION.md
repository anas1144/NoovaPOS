# Hybrid database-per-tenant — cut-over runbook

This is the end-to-end procedure to move NoovaPOS from a single central database
(row-level `tenant_id` scoping) to the **hybrid** model:

- **Central DB** — platform-only tables: `tenants`, `domains`, `plans`,
  `subscriptions`, `audit_logs`, `tenant_backups`, plus the super-admin identity
  rows.
- **Per-tenant DB** — identity (`users`, `roles`, `permissions`,
  `personal_access_tokens`, …) **and** all business tables (products, sales,
  purchases, inventory, customers, suppliers, settings, etc.).

The mechanism is already wired and **safe by default** — the runtime DB switch is
off until you set an env flag, so the app keeps running on the central DB until
you deliberately cut over.

---

## What is already in place (code)

| Piece | File | State |
|------|------|-------|
| Per-tenant DB bootstrapper | `config/tenancy.php` (`bootstrappers`) | enabled (inert until switch on) |
| Central connection | `config/database.php` (`central`) | added |
| Tenant DB created+migrated on onboarding | `app/Providers/TenancyServiceProvider.php` (`TenantCreated` pipeline) | `CreateDatabase`, `MigrateDatabase` on; `SeedDatabase` commented |
| Runtime DB switch (per request) | `TenancyServiceProvider` `TenancyInitialized` | **gated by `TENANCY_DB_SWITCH` env, default false** |
| Pin platform models to central | `Plan`, `Subscription`, `AuditLog` (use `App\Traits\CentralConnection`) | done |
| Central-connection trait | `app/Traits/CentralConnection.php` | added |
| Tenant migration set | `database/migrations/tenant/` | baseline only — populate with the command below |
| Relocation command | `app/Console/Commands/RelocateBusinessMigrations.php` | added |

---

## Why `tenant_id` columns and `Multitenantable` can stay

Inside a tenant's own database every row already belongs to that one tenant, so
the existing `tenant_id` column and the `Multitenantable` global scope
(`where tenant_id = Auth::user()->tenant_id`) are a harmless no-op — every row
matches. **You do not need to edit the 162 migrations' contents.** This is what
makes the cut-over tractable.

---

## Cut-over steps (run on a machine with MySQL)

> Back up your central database first.

1. **Populate the tenant migration set** (copy, non-destructive):

   ```bash
   php artisan tenancy:relocate-business-migrations          # dry run, review the list
   php artisan tenancy:relocate-business-migrations --copy   # perform the copy
   ```

   This copies every migration into `database/migrations/tenant` except the
   platform-only ones (tenants, domains, plans, subscriptions, audit_logs,
   tenant_backups).

2. **Provision + migrate every existing tenant database:**

   ```bash
   php artisan tenants:migrate --force
   ```

   (New tenants are migrated automatically by the `TenantCreated` pipeline.)

3. **Re-enable tenant seeding** so each tenant DB gets its default settings,
   currencies, walk-in customer and default warehouse. In
   `TenancyServiceProvider` uncomment `Jobs\SeedDatabase::class` in the
   `TenantCreated` pipeline, then for existing tenants:

   ```bash
   php artisan tenants:seed --force
   ```

   `TenantDatabaseSeeder` already guards itself until the business tables exist.

4. **Seed each tenant's owner + roles into the tenant DB.** Onboarding currently
   writes the owner `User`, role and `UserStore` to the central DB
   (`app/Services/TenantOnboardingService.php`). After the switch, tenant users
   must live in the tenant DB. Wrap the owner/store/settings creation in
   `$tenant->run(function () { ... })` so it executes in tenant context. (Tenant
   record, domain and subscription stay central.)

5. **Flip the switch:**

   ```env
   TENANCY_DB_SWITCH=true
   ```

   Now every tenant-subdomain request switches the default connection to that
   tenant's database. Super-admin requests on the central domain stay on central.

6. **Verify** (see checklist).

---

## Known cross-connection caveats to fix during verification

With the default connection switching per request, watch for code that assumes a
single connection:

1. **`exists:` / `unique:` validation rules** run on the *default* connection.
   Rules that reference a **central** table from a tenant request (e.g.
   `exists:plans,id`) must be qualified with the central connection, e.g.
   `Rule::exists('central.plans', 'id')`. Rules referencing tenant tables are
   fine.

2. **Explicit cross-DB JOINs.** Eloquent `belongsTo`/`hasMany` across connections
   work (separate queries), but raw `->join('central_table', …)` or `whereHas`
   spanning central↔tenant will fail. The platform/super-admin queries in
   `PlatformTenantController` already read central tables and must run on the
   central connection (they do — those models are pinned).

3. **Sanctum tokens.** `personal_access_tokens` is copied into the tenant set so
   tenant-user auth works inside tenant context; the central copy serves super
   admins. No code change needed, but confirm login works on both a tenant
   subdomain and the central domain.

4. **Queues / jobs.** `QueueTenancyBootstrapper` is enabled, so queued jobs carry
   and restore tenant context. Ensure your worker is running with the same env.

---

## Verification checklist

- [ ] `php artisan tenants:migrate --force` completes with no errors.
- [ ] A new tenant onboarding creates a `tenant<id>` database with all tables.
- [ ] Super-admin login on the central domain works and Tenants page loads.
- [ ] Tenant-owner login on `*.noovapos.local` works.
- [ ] Creating a product on a tenant writes to that tenant's DB only
      (`SELECT COUNT(*) FROM tenant<id>.products` increases; central `products`
      unchanged).
- [ ] Two tenants cannot see each other's products.
- [ ] Reports / POS screens that join inventory tables still render.

---

## Rollback

Set `TENANCY_DB_SWITCH=false`. The app reverts to central-DB behaviour
immediately (tenant DBs are simply left dormant). Optionally delete
`database/migrations/tenant/*` (except baseline + this doc) to undo the copy.
