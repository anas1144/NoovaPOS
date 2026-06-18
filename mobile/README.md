# NoovaPOS Mobile (Flutter)

Offline-first native app for cashiers, waiters, delivery staff and attendance,
talking to the NoovaPOS API (`/api/v1`, see `../docs/API-CONTRACT.md`).

## Run (development)

```bash
cd mobile
flutter pub get
flutter run
```

> This folder contains the hand-written app source. If you don't yet have the
> platform folders (`android/`, `ios/`), generate them once without overwriting
> `lib/` or `pubspec.yaml`:
>
> ```bash
> flutter create . --org com.noovapos --project-name noovapos_mobile
> ```

On launch the app asks for your **Server URL** (tenant web address), then signs
in with email/password. The token (Sanctum) and base URL are kept in secure
storage.

## What's here (F-CP1)

```
lib/
  main.dart                 app entry (Riverpod ProviderScope)
  app.dart                  root — routes by session phase
  core/
    api_client.dart         Dio client (baseUrl + bearer token, envelope helper)
    session.dart            SessionController: setup → login → /v1/me → ready
  features/
    setup_screen.dart       enter server URL
    login_screen.dart       email/password sign-in
    home_screen.dart        role-aware module grid (placeholders)
```

- **Auth**: `POST /api/login` → token; `GET /api/v1/me` → user, features, roles;
  `POST /api/logout`.
- **Role-aware home**: shows POS / Waiter / Deliveries / Attendance / Dashboard
  tiles based on the signed-in role and feature flags.

## POS core + offline sync (F-CP2)

```
lib/
  data/
    local_db.dart      sqflite cache (products, customers) + offline sale queue
    sync_service.dart  bootstrap download, device register, push pending batch
  features/pos/
    cart.dart          cart state (lines, qty, total)
    pos_screen.dart    search (cached) → cart → Charge → queue + sync
```

- **Offline-first:** every sale is saved to the local `pending_sales` queue with a
  `local_uuid`, then uploaded to `POST /api/offline-sync/batches` (the existing
  server engine, idempotent on `local_uuid`). If offline, it stays queued and
  flushes automatically next time the POS opens or you tap **Sync**.
- **Cache:** first online launch downloads `/api/v1/sync/bootstrap` into SQLite;
  product search works fully offline afterwards.
- The POS tile on the home screen opens this. Uses `sqflite` (no codegen).

## Printing & scanning (F-CP3)

- **Bluetooth thermal receipts** — `core/printing.dart` (`print_bluetooth_thermal`
  + `esc_pos_utils_plus`): pick a paired printer (🖨 in the POS app bar; MAC is
  remembered), and each completed sale prints a 58 mm ESC/POS receipt
  (best-effort — silently skipped if no printer is selected).
- **Camera barcode/QR** — `features/pos/scan_screen.dart` (`mobile_scanner`): the
  scan button (▣) opens the camera; a decoded code is looked up by product
  `code` in the local cache and added to the cart (works offline). Also supports
  torch + camera switch.

### Native permissions (add after `flutter create .`)

- **Android** `android/app/src/main/AndroidManifest.xml`:
  `CAMERA`, `BLUETOOTH_CONNECT`, `BLUETOOTH_SCAN` (and `BLUETOOTH`/`BLUETOOTH_ADMIN`
  for older APIs).
- **iOS** `ios/Runner/Info.plist`: `NSCameraUsageDescription`,
  `NSBluetoothAlwaysUsageDescription`.

## Role apps (F-CP4)

The home tiles now open working screens:

- **Waiter / KOT** (`features/waiter/`) — pick a table, build an order from cached
  products, **Send to Kitchen** (`POST /api/restaurant/kots`).
- **Deliveries** (`features/delivery/`) — assigned deliveries (`GET /api/deliveries`)
  with status updates: out-for-delivery / delivered / failed
  (`POST /api/deliveries/{sale}/status`).
- **Attendance** (`features/attendance/`) — self check-in/out, start/end break,
  and **scan-to-check-in** (`/api/attendance/status` · `/check-in` · `/check-out`
  · `/break/start|end`).
- **Dashboard** (`features/dashboard/`) — today's + overall sale/purchase counts
  (`/api/today-sales-purchases-count`, `/api/all-sales-purchases-count`).

Each tile is shown only for roles/features that have it (see `home_screen.dart`).

## Push notifications & release (F-CP5)

### Push (FCM)

`core/push.dart` registers the device's FCM token with the backend
(`POST /api/v1/devices`) once signed in, refreshes it on rotation, and handles
foreground messages. It's **optional**: until Firebase config is added the code
catches the init error and simply skips push.

To enable:

1. Create a Firebase project, add Android + iOS apps.
2. Drop `android/app/google-services.json` and
   `ios/Runner/GoogleService-Info.plist`.
3. Add the Google services Gradle plugin (Android) per FlutterFire docs, or run
   `flutterfire configure`.

### Android signing & build

```bash
# 1) create a keystore
keytool -genkey -v -keystore upload-keystore.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload
```

`android/key.properties`:
```
storeFile=upload-keystore.jks
storePassword=…
keyAlias=upload
keyPassword=…
```

In `android/app/build.gradle`, load `key.properties` and set the `release`
`signingConfig` (standard Flutter signing setup). Then:

```bash
cd mobile
flutter build apk --release          # build/app/outputs/flutter-apk/app-release.apk
flutter build appbundle --release    # build/app/outputs/bundle/release/app-release.aab  (Play)
```

### iOS

Open `ios/Runner.xcworkspace` in Xcode, set a Team + bundle id, then
`flutter build ipa --release` (requires a paid Apple Developer account).

### CI

`.github/workflows/mobile-release.yml` builds the APK + AAB on a `v*` tag
(signs automatically when the keystore secrets are present).

## Status

Mobile app complete (F-CP1…F-CP5): offline-first POS, role apps
(Waiter/Delivery/Attendance/Dashboard), Bluetooth printing, camera scanning,
and push. Pair with the API contract in `../docs/API-CONTRACT.md`.
