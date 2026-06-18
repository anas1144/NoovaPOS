# NoovaPOS — App API Contract (v1)

Stable surface the **Flutter mobile** and **Electron desktop** apps build on.
Base URL is the tenant's domain, e.g. `https://abcgroup.noovapos.local/api`.

## Auth (Sanctum bearer tokens)

- `POST /api/login` — `{ email, password }` → `{ data: { token, ... } }`.
  The token is a Sanctum personal access token. Send it on every request:
  `Authorization: Bearer <token>`.
- `POST /api/logout` — revokes the current token.
- `GET /api/v1/me` — identity bundle: `{ user, features, permissions }`
  (`features` is the effective per-store feature map; `permissions` is the
  flattened permission names). Call once after login to drive role-aware UI.

**Tenant resolution:** apps talk to the tenant's own subdomain, so tenancy is
resolved by host. No extra header is required.

## Offline first-load

- `GET /api/v1/sync/bootstrap?limit=5000&customer_limit=5000` →
  `{ synced_at, products[], customers[], price_tiers[], settings }`.
  Cache locally (Drift/SQLite on mobile, IndexedDB on desktop). `products` are
  lightweight `{id,name,code,price,cost}`; fetch full detail on demand from the
  existing product endpoints.

## Offline sales upload (existing engine)

Reuse the existing offline-sync pipeline (do **not** invent a new one):

- `POST /api/offline-devices/register` — register the terminal/device.
- `POST /api/offline-devices/{id}/heartbeat` — liveness.
- `POST /api/offline-sync/batches` — upload a batch of locally-created sales.

**Idempotency:** every locally-created sale carries a client-generated
`local_uuid`; the server de-duplicates on it, so re-sending a batch is safe.
Mark a sale `synced` only after the server acknowledges its `local_uuid`.

## Push notifications

- `POST /api/v1/devices` — `{ token, platform: android|ios|desktop|web, device_name }`
  registers an FCM/APNs token for the user.
- `DELETE /api/v1/devices` — `{ token }` unregisters (on logout).

## Conventions

- All responses are wrapped: `{ success, data, message }`.
- Errors return non-2xx with `{ message }`.
- Money/quantities are numbers; dates are ISO-8601 (`YYYY-MM-DD` or full).
- Feature gating: hide UI for any feature key that is `false` in `me().features`.

## Versioning

App-facing endpoints live under `/api/v1/*` so app releases can pin a version.
`login` / `logout` / `offline-sync` are shared with the web app and unversioned.
