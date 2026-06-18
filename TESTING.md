# NoovaPOS — Automated Testing Guide

How to auto-test each layer of the stack and the exact commands to run.

| Layer | Tool | Command |
|-------|------|---------|
| Backend (Laravel API) | PHPUnit | `php artisan test` |
| Backend static analysis | Laravel Pint (style) | `./vendor/bin/pint --test` |
| Frontend (React) | Vitest + Testing Library | `npm run test` |
| Frontend build sanity | Vite | `npm run build` |
| API smoke (black-box) | bash script | `bash scripts/smoke.sh <url>` |
| Desktop (Electron) | Playwright for Electron | `cd electron && npm test` |
| Mobile (Flutter) | flutter test + analyze | `cd mobile && flutter test` |

A green run of the first row already covers the most important surface (the API
that the web, desktop and mobile clients all depend on).

---

## 1. Backend — Laravel (PHPUnit)

The project ships with `phpunit.xml` and a `tests/` folder (Feature + Unit). Run:

```bash
php artisan test            # all tests
php artisan test --parallel # faster, multi-process
php artisan test --filter=FbrDi   # only matching tests
```

### Test database

Tests should NOT touch your dev DB. The fastest setup is an in-memory SQLite
DB — add this to `phpunit.xml` under `<php>` (or use a `.env.testing`):

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
```

Use the `RefreshDatabase` trait in a test to migrate a fresh schema per run.

> Multi-tenancy note: feature tests that need tenant data should run against the
> **central** connection or bootstrap a test tenant. For pure API/validation
> tests (most of them) no tenant is required — see the example below.

### Writing a feature test

`tests/Feature/` example (also shipped as `tests/Feature/ApiSmokeTest.php`):

```php
public function test_login_requires_credentials(): void
{
    $this->postJson('/api/login', [])->assertStatus(422);
}
```

Patterns you'll reuse:

```php
$this->actingAs($user, 'sanctum');               // authenticated calls
$this->postJson('/api/fbr-di/invoices', $payload)->assertOk();
$this->assertDatabaseHas('fbr_di_invoices', ['status' => 'draft']);
```

### Style / lint

```bash
composer require laravel/pint --dev   # once
./vendor/bin/pint --test              # check formatting (no changes)
./vendor/bin/pint                     # auto-fix
```

---

## 2. Frontend — React (Vitest)

The app builds with Vite, so **Vitest** is the natural test runner (Testing
Library is already a dependency). One-time setup:

```bash
npm i -D vitest @vitejs/plugin-react jsdom @testing-library/jest-dom
```

Add to `package.json` scripts:

```json
"test": "vitest run",
"test:watch": "vitest"
```

Create `vitest.config.js`:

```js
import { defineConfig } from "vitest/config";
import react from "@vitejs/plugin-react";
export default defineConfig({
  plugins: [react()],
  test: { environment: "jsdom", globals: true },
});
```

Example `resources/pos/src/__tests__/cart.test.jsx`:

```jsx
import { render, screen } from "@testing-library/react";
import { prepareCartArray } from "../frontend/shared/PrepareCartArray";

test("prepareCartArray prices a product at the retail tier", () => {
  const cart = prepareCartArray([
    { id: 1, attributes: { name: "A", product_price: 100, tax_type: 1, order_tax: 0, stock: { quantity: 5 } } },
  ]);
  expect(cart[0].product_price).toBe(100);
});
```

Run: `npm run test`. For a quick "does it still compile" gate, `npm run build`
is the cheapest signal in CI.

---

## 3. API smoke tests (black-box, no codebase needed)

`scripts/smoke.sh` hits the running server and checks key endpoints respond.
Run it against any environment:

```bash
bash scripts/smoke.sh http://abcgroup.noovapos.local
```

It verifies login validation, that protected routes require auth, and that the
SPA loads. Extend it with a real token to cover authenticated endpoints. (You
can also import the endpoints into Postman/Newman and run `newman run`.)

---

## 4. Desktop — Electron

Two levels:

- **Syntax / config gate (cheap):** `node -c electron/main.js` etc. — already
  part of normal dev.
- **End-to-end:** Playwright drives the packaged app:

```bash
cd electron
npm i -D @playwright/test playwright
# test that the window opens and loads the setup screen
npx playwright test
```

A minimal `electron/tests/launch.spec.js` launches the app via
`_electron.launch({ args: ["."] })` and asserts the setup page renders.

---

## 5. Mobile — Flutter

```bash
cd mobile
flutter pub get
flutter analyze          # static analysis (lints, type errors)
flutter test             # unit + widget tests
```

Example `mobile/test/cart_test.dart`:

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:noovapos_mobile/features/pos/cart.dart';

void main() {
  test('cart totals add up', () {
    final c = CartController();
    c.add({'id': 1, 'name': 'A', 'price': 100.0});
    c.add({'id': 1, 'name': 'A', 'price': 100.0}); // same product → qty 2
    expect(c.total, 200.0);
  });
}
```

Widget tests use `testWidgets(...)` + `tester.pumpWidget(...)`.

---

## 6. Run everything (locally)

```bash
# backend
php artisan test
# frontend
npm run test && npm run build
# mobile
( cd mobile && flutter analyze && flutter test )
# desktop
( cd electron && node -c main.js && node -c printer.js )
```

---

## 7. CI (already wired)

- `.github/workflows/ci.yml` — spins up MySQL + Redis, installs deps, builds
  assets and runs `php artisan test` on every push/PR to `main`/`develop`, then
  builds the Docker image on `main`.
  - ⚠️ Fix: the build step runs `npm run prod`, but this repo's build script is
    `npm run build` — update that line (and add `npm run test` before it once
    Vitest is set up).
- `.github/workflows/desktop-release.yml` — builds the Electron installers.
- `.github/workflows/mobile-release.yml` — builds the Flutter APK/AAB (runs
  `flutter analyze`).

Add a `mobile`/`frontend` test job to `ci.yml` to gate PRs on all three clients.

---

## What to test first (highest value)

1. **API contract** — auth (`/api/login`, `/api/v1/me`), and one create+read per
   module (sale, FBR-DI invoice, attendance check-in). These protect every client.
2. **Money math** — cart totals, invoice tax (`prepareCartArray`, FBR-DI
   `recalculate`, recurring totals).
3. **Offline sync idempotency** — same `local_uuid` posted twice creates one row.
4. **Permission gates** — a cashier cannot hit manager-only endpoints (403).
