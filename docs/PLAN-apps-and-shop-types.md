# NoovaPOS — Plan: Mobile (Flutter) + Desktop (Electron) + Remaining Shop Types

This plan covers three workstreams. Each is delivered in **checkpoints** (same
rhythm as the rest of the project): you review each before the next.

> Foundation that already exists and both apps reuse: the Laravel **API**
> (Sanctum tokens), **multi-tenant** subdomains, **feature flags** (`/api/my-features`),
> **offline-first** contract (local_uuid + sync queue + idempotency), Meilisearch
> product search, and the **attendance / FBR-DI** modules.

---

## A. Shared groundwork (do once, both apps depend on it) — **CP0**

Neither app should hard-coordinate with the web SPA; they talk to the API. A
small amount of API hardening makes both clean.

1. **Mobile/desktop auth**: confirm Sanctum **token** issuance (`/api/login`
   returning a bearer token, not just SPA cookie). Add a `POST /api/auth/token`
   (device name) + `POST /api/auth/logout` if missing. Tenant resolution by
   subdomain header `X-Tenant` for non-cookie clients.
2. **Sync contract**: document + freeze the offline sale payload
   (`local_uuid`, `synced_at`, idempotency key) and a `POST /api/sync/sales`
   batch endpoint + `GET /api/sync/bootstrap` (products, customers, settings,
   price tiers, taxes) for first-load caching.
3. **Versioned API** namespace `/api/v1/...` alias so app releases pin a version.
4. **Push tokens**: `device_tokens` table + `POST /api/devices` to register FCM
   tokens (free tier) for notifications.

Deliverable: a short `API-CONTRACT.md` + the few endpoints above. ~1 checkpoint.

---

## B. Desktop app — **Electron** (Windows / macOS / Linux)

**Approach:** wrap the **existing React POS** (it already builds with Vite and is
offline-first) in an Electron shell, and add the native capabilities a counter
needs. This reuses 100% of the POS UI — no rebuild.

**Why wrap, not rebuild:** the web POS is feature-complete (cart, tiers, deals,
mixed units, KOT, FBR, attendance). Electron adds hardware + offline packaging.

### Architecture
```
electron/
  main.js            # app lifecycle, window, auto-update, IPC handlers
  preload.js         # safe bridge (contextIsolation) → window.noova.*
  printer.js         # ESC/POS thermal print (node-thermal-printer / escpos)
  drawer.js          # cash-drawer kick (via printer or serial)
  serial.js          # serial/USB scale + scanner passthrough
  store.js           # local config (electron-store)
builder config        # electron-builder → .exe (NSIS), .dmg, AppImage
```
The renderer loads the built SPA (`public/` / `dist`) locally for offline use, or
the tenant URL when online. `window.noova` exposes `print(receipt)`,
`openDrawer()`, `onScan(cb)`, `getPrinters()`.

### Native features
- Thermal **receipt printing** (ESC/POS, network + USB), **cash-drawer** kick.
- **Barcode scanner** (USB-HID works as keyboard already; serial scanners via
  `serial.js`).
- **Customer display** on a 2nd monitor (reuse the existing customer-display page
  in a second BrowserWindow).
- **Offline**: bundle the SPA + IndexedDB cache already in the web app; background
  sync when the network returns.
- **Auto-update** (electron-updater + GitHub/S3 feed), **kiosk/full-screen**,
  **deep links** (`noovapos://`).

### Checkpoints
- **E-CP1** Scaffold: `electron/`, load the SPA, dev + build scripts, single
  window, app icon.
- **E-CP2** Printing + cash drawer + printer settings UI.
- **E-CP3** Offline packaging + auto-update + customer-display window.
- **E-CP4** `electron-builder` installers (Win/Mac/Linux) + signing notes.

---

## C. Mobile app — **Flutter** (Android / iOS)

**Approach:** a **new Flutter app** that consumes the API and is **offline-first**
(local DB + sync queue mirroring the web contract). Not a webview — native UI for
speed, scanning and printing.

### Target users (one app, role-aware home)
- **Cashier** — quick POS: search, cart, payment, receipt.
- **Waiter** — tables, send-to-kitchen (KOT), running bill (restaurant).
- **Delivery** — assigned deliveries, mark delivered, collect cash (water /
  distribution / delivery_boy).
- **Attendance** — face / barcode / QR / manual check-in (reuses the attendance
  API), break/task.
- **Manager/Owner** — dashboard KPIs, approvals.

### Stack
- **State**: Riverpod. **Routing**: go_router. **HTTP**: Dio (+ retry,
  auth interceptor). **Local DB**: Drift (SQLite) for products/customers/sales
  cache + sync queue. **Models**: freezed/json_serializable.
- **Scanning**: `mobile_scanner` (camera) + external HID.
- **Printing**: `esc_pos_bluetooth` / `print_bluetooth_thermal` for portable
  thermal printers.
- **Camera/face**: `google_mlkit_face_detection` for attendance (on-device).
- **Push**: `firebase_messaging` (free tier).

### Project shape
```
lib/
  core/        api client, auth, tenant, offline sync engine, theme
  data/        drift db, repositories, dtos
  features/
    auth/  pos/  cart/  payments/  receipt/  restaurant/  delivery/
    attendance/  dashboard/  settings/
  app.dart  router.dart
```

### Checkpoints
- **F-CP1** Scaffold + theme + **login (Sanctum)** + tenant select + API client +
  secure token storage + role-aware home.
- **F-CP2** **POS core**: product search (online + cached), cart (tiers/units),
  payment, **offline sale** → local queue → background sync.
- **F-CP3** **Receipt printing** (Bluetooth thermal) + **barcode/QR scanning**.
- **F-CP4** **Role apps**: waiter (KOT), delivery board, **attendance check-in**
  (face/scan), manager dashboard.
- **F-CP5** Push notifications + polish + **build/release** (signed APK / Play
  internal track; iOS notes).

---

## D. Complete the remaining shop types

Module lists exist in `Plan::SHOP_TYPE_MODULES`; these checkpoints implement the
**actual features** (DB + API + React pages + sidebar, feature-flag gated). Retail
and Restaurant and FBR-Digital are done.

| Shop type | What's missing to "complete" | Checkpoint |
|---|---|---|
| **Pharmacy** | Batch + **expiry tracking** on stock, near-expiry alerts, batch-wise sale | S-CP1 |
| **Water Supply** | **Bottle/asset tracking**, **deposits & returns**, **route management**, recurring delivery schedule polish | S-CP2 |
| **Bakery** | **Production/recipe** (raw → finished), wastage, day-part stock | S-CP3 |
| **Electronics** | **Serial-number** tracking, **warranty** registration & lookup | S-CP4 |
| **Fashion** | **Variations** (size/color matrix) verify + barcode per variant | S-CP5 |
| **Distribution** | **Route/van** assignment, load-out, delivery scheduling | S-CP6 |
| **Monthly Service** | **Recurring invoices**, subscription billing cycles, **overdue** tracking & reminders | S-CP7 |
| **Custom** | Generic toggles (already covered by feature flags) | — |

Each shop-type checkpoint = migrations + models + controller + React page(s) +
sidebar entries gated by the shop type / feature flag, plus seeders/demo data.

---

## Suggested build order

1. **CP0** API contract + token auth + sync/bootstrap endpoints (unblocks both apps).
2. **Electron E-CP1→E-CP4** (fastest win — reuses the web POS; usable desktop POS quickly).
3. **Shop types S-CP1→S-CP7** (server features; benefit web + both apps at once).
4. **Flutter F-CP1→F-CP5** (most work; depends on CP0 + benefits from shop-type APIs).

Rationale: Electron gives a shippable desktop POS soonest; finishing shop types
improves every client; Flutter is the largest effort and consumes the hardened API.

---

## Decisions to confirm before CP0

1. **Electron** = wrap the existing React POS (recommended) vs build a separate
   desktop UI.
2. **Flutter** = offline-first native (recommended) vs thin online-only client.
3. **Build order**: the order above (Electron → shop types → Flutter) vs your
   preferred sequence.
4. **Repos**: keep `electron/` and a `mobile/` (Flutter) folder **inside** this
   repo, or separate repositories.
