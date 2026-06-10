````````````````# Tenant migrations

Migrations in this folder run **inside each tenant's own database**, not the
central database.

## How they run

- Automatically on onboarding: creating a `MultiTenant` fires `TenantCreated`,
  which runs the `CreateDatabase → MigrateDatabase → SeedDatabase` job pipeline
  (see `App\Providers\TenancyServiceProvider`).
- Manually for all tenants: `php artisan tenants:migrate`
- Fresh rebuild for all tenants: `php artisan tenants:migrate-fresh`
- Seed all tenants: `php artisan tenants:seed`

The path is configured in `config/tenancy.php → migration_parameters`, which
points here whenever `database/migrations/tenant` exists.

## Rules

1. **No `tenant_id` columns.** Each tenant has its own database, so isolation is
   physical. Adding `tenant_id` here is redundant and wrong.
2. Central-only tables (`tenants`, `domains`, `plans`, `subscriptions`,
   `audit_logs`, the users index used by the super admin, etc.) stay in
   `database/migrations` (central) — do **not** copy them here.
3. Business-domain tables (products, categories, sales, purchases, inventory,
   stock movements, expenses, customers, suppliers, warehouses, recurring,
   restaurant, fbr invoices, etc.) belong here.

## Migration plan (incremental cut-over)

The runtime DB switch (`Listeners\BootstrapTenancy` on `TenancyInitialized` in
`TenancyServiceProvider`) is intentionally **disabled** so the existing
central-DB queries keep working. To finish the cut-over:

1. Move each business migration from `database/migrations` to this folder and
   drop its `tenant_id` column + tenant global scope from the model.
2. Run `php artisan tenants:migrate` to apply them to every tenant DB.
3. Re-enable `Listeners\BootstrapTenancy` so tenant requests switch to the
   tenant database.
4. Update super-admin/platform queries that currently use
   `whereIn('tenant_id', ...)` to either read from the central index tables or
   to run inside each tenant's context via `$tenant->run(fn () => ...)`.
