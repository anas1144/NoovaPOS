# NoovaPOS — Feature List

Everything implemented in the platform, the desktop app and the mobile app.

---

## 1. SaaS Platform & Multi-Tenancy
- Multi-tenant via Stancl Tenancy — subdomain per tenant, isolated data.
- **Hybrid database mode** — shared DB or **separate DB per tenant** (flag-gated,
  `TENANCY_DB_SWITCH`), with auto provisioning on tenant creation (queued).
- Platform super-admin separated from tenant workspace (system-only menu).
- Tenants list — one row per tenant with nested stores; impersonate, suspend.
- Hierarchy: **Tenant (Company) → Store/Branch → Shop/POS counter → Register → User**.

## 2. Billing & Subscriptions
- Plans with country-based visibility, separate-DB pricing, trial, limits
  (stores/shops/registers/users/products).
- Subscriptions with hard-lock on expiry (over-limit products hidden, over-limit
  users/shops blocked).
- Add-ons (extra shops/users/products), FBR add-on (Pakistan only).
- **Two payment methods, super-admin toggled:** *Request* (manual proof upload)
  and *Online Checkout* — global hosted **payment link** + Pakistan locals
  (JazzCash, Easypaisa, HBL, Meezan, UBL…). Hosted pay page `/billing/pay/{token}`.
- Country-aware payout bank accounts; long-term (≥N months) discounts; per-country
  + global pricing. Super-admin confirms/rejects every payment.

## 3. Roles & Permissions
- Dynamic Spatie roles/permissions; role + permission **templates per shop type**
  (super-admin managed).
- Roles: platform_super_admin, admin, tenant_owner, branch_manager, shop_manager,
  cashier, waiter, **kitchen**, accountant, inventory_manager, delivery_staff,
  **delivery_boy**, **fbr_agent**.
- Module- and shop-level access control; feature gating via `/api/my-features`.

## 4. POS Core
- Fast cart, barcode scanning, multi-/split payment, credit sales, returns, hold,
  quotations, receipts, QR invoices, keyboard shortcuts.
- **Price tiers** (retail/wholesale/M20…) with per-product overrides + customer
  default tier; in-cart tier selector.
- **Deals / combos** — Add Deal expands a combo into priced product lines.
- **Mixed units** (box/pack/piece → base qty + `unit_breakdown`).
- **Feature flags** — global (super-admin) ∧ per-store, gate POS UI.
- Virtualized rendering, Meilisearch/cached search for 100k+ products.

## 5. Shop Types (11)
- **Retail** — inventory, barcode, GRN, transfers, suppliers.
- **Restaurant** — halls, tables (seats), kitchens, **KOT routing + Send-to-Kitchen
  with delta reprint**, kitchen display, dine-in/takeaway/delivery.
- **Pharmacy** — **batch + expiry tracking**, near-expiry alerts.
- **Water Supply** — delivery **routes**, **bottle ledger** (issue/return +
  balances), **deposits** & refunds.
- **Bakery** — **recipes** (ingredients) + **production runs** (consumption/yield/
  wastage).
- **Electronics** — **serial-number** tracking + **warranty** register & lookup.
- **Fashion** — **variations** (size/color) + per-variant barcode.
- **Distribution** — **van load-out / dispatch** + reconciliation (delivered/
  returned).
- **Monthly Service** — recurring plans, subscriptions, billing cycles, invoices,
  **overdue tracking + reminders**.
- **FBR Digital Invoice** — see §9.
- **Custom** — generic, feature-flag driven.

## 6. Inventory & Accounting
- Event-driven stock via stock movements; warehouses, GRN, purchase orders,
  transfers, adjustments, serials, batches, expiry, low-stock alerts.
- Accounting: chart of accounts, journal entries, trial balance, P&L, ledgers,
  expenses, auto-posting from sales/purchases.

## 7. Attendance Management (POS-integrated)
- Kiosk **check-in/out** with **Face (real, face-api.js)**, **Barcode/QR**
  (camera + USB), **Fingerprint** (camera visual record, future-ready), **Manual**.
- Smart flow: first scan of the day = instant check-in; after that an action panel
  (tasks, break, check-out).
- **Tasks** (office/personal: start/pause/resume/complete), **breaks**.
- **Dashboard** (cards + charts + live timeline), **Live board**.
- **Reports** (summary / productivity / performance), **Requests** (employee
  correction → manager approve), **Configuration**.
- **No-code Device Connectors** — register any external scanner / face terminal /
  cloud API by config (endpoint + auth + request/response mapping), server- or
  client(localhost)-run, no code change.
- **WebAuthn** — real device-sensor biometric (laptop/phone fingerprint/face), no
  external hardware, signature verified server-side via openssl.
- Full `attendance.*` permission set.

## 8. FBR (Pakistan) — POS add-on
- Sandbox/production, invoice queue, retry, QR codes, FBR logs, pending-sync
  dashboard; NTN/STRN/province/POS-ID profiles. Pakistan-only, async invoice no.

## 9. FBR Digital Invoice (standalone shop type)
- Dedicated FBR invoicing, **no POS billing**. Company=Tenant, Business=Store;
  **agent** manages many companies.
- Businesses (seller NTN/STRN/tokens), **invoices** (draft → controlled **sync**
  → accepted/rejected, locked once synced), line items + auto totals.
- **Controlled sync** (permission-gated, queued job, QR, error logging),
  **Error Center**, **Sandbox Testing** (SN001–028), **Dashboard**, **Reports**
  (sales/tax/sync/rejected).
- Per-company **quotas** (Single Company / Agent plans), super-admin **limits +
  agent assignment**, audit logs, CMS landing page.

## 10. Marketing CMS
- Shop-type pages, FBR pages, blog with SEO, per-country section visibility,
  dynamic landing menu, super-admin managed; public `/p/{slug}`, `/blog`.

## 11. Offline-First & Sync
- PWA, IndexedDB, sync queue, idempotency (`local_uuid`), offline devices, batch
  upload, conflict-safe re-send.

## 12. Notifications & AI
- Queue-based email/SMS/WhatsApp/push/in-app; **FCM push** for apps.
- AI-style insights: sales/stock prediction, reorder suggestions, anomaly notes
  (no paid API).

## 13. Desktop App (Electron) — `electron/`
- Wraps the React POS; configurable server URL, kiosk mode, zoom.
- **Native ESC/POS thermal printing** (network) + **system/USB printing** + **cash
  drawer**; printer settings + test print.
- **Offline fallback** + PWA cache; **auto-update** (electron-updater);
  **2nd-monitor customer display** (fullscreen).
- Signed installers — Win NSIS / Mac dmg / Linux AppImage+deb; CI release workflow.

## 14. Mobile App (Flutter) — `mobile/`
- **Offline-first**: sqflite cache + pending-sale queue → idempotent sync to the
  server (`/offline-sync/batches`).
- Server-URL setup + Sanctum login + `/v1/me` role-aware home.
- **POS** (cached search, cart, charge), **Waiter/KOT**, **Delivery** board,
  **Attendance** (self check-in/out, break, scan), **Manager dashboard**.
- **Bluetooth thermal receipts** (ESC/POS) + **camera barcode/QR scanning**.
- **FCM push** registration; signed APK/AAB build + CI.

## 15. API & DevOps
- Versioned **`/api/v1`** app surface (`me`, `sync/bootstrap`, `devices`) +
  `device_tokens`; documented in `docs/API-CONTRACT.md`.
- Redis, queues, **Horizon** (dashboard `/horizon`, super-admin only), Supervisor,
  Docker, Cloudflare, S3 backups.
- CI: `ci.yml` (Laravel tests + Docker), desktop + mobile release workflows.
- Audit logs, rate limiting, tenant isolation, permission middleware.

---

## Future / not-yet-built (suggested next)
- Liveness/anti-spoofing for face attendance; hardware fingerprint SDK adapters.
- WebSocket live updates (currently AJAX polling) via Laravel Reverb.
- Full double-entry accounting reports UI; bank reconciliation UI.
- Mobile: full receipt designer, returns/refunds, customer management screens.
- E-invoicing for other countries; tax-rule engine per region.
- Frontend (Vitest) + mobile (flutter test) jobs added to CI.
