# NoovaPOS — Build & Run (all targets)

One place for every build/run command: backend, web, desktop (Electron), mobile
(Flutter). For a guided first-time setup see the **Quick Start** in `README.md`;
for tests see `TESTING.md`.

---

## Prerequisites

| Target | Needs |
|--------|-------|
| Backend + web | PHP 8.2+, Composer, Node 18+, MySQL 8, Redis |
| Desktop | Node 18+ (mac builds require macOS; win builds require Windows or CI) |
| Mobile | Flutter SDK 3.3+, Android Studio (Android), Xcode + Apple account (iOS) |

---

## 1. Backend (Laravel API + web POS)

```bash
composer install
composer require laravel/horizon          # first time (queues dashboard)
cp .env.example .env                        # Windows: copy
php artisan key:generate
php artisan migrate:fresh --seed            # schema + demo data  (or: migrate)
php artisan storage:link
php artisan horizon                         # queue worker (FBR sync, imports, tenant DBs)
```

Edit `.env`: `APP_URL`, `DB_*`, `REDIS_*`, `QUEUE_CONNECTION=redis`,
`TENANCY_CENTRAL_DOMAINS=noovapos.local`. Add hosts entries for
`noovapos.local` + each tenant subdomain.

## 2. Web frontend (React via Vite)

```bash
npm install
npm run fetch:face-models     # one-time: self-hosted face-recognition weights
npm run build                 # production assets  (or: npm run dev for hot reload)
```

Open `http://noovapos.local` (super admin) / `http://abcgroup.noovapos.local`
(tenant). Logins: see README → Demo Users (password `123456`).

## 3. Desktop app (Electron) — `electron/`

```bash
cd electron
npm install
npm start                     # run the desktop POS (asks for Server URL on first run)
```

Build installers (output → `electron/dist/`):

```bash
npm run dist:win              # NSIS .exe   (run on Windows)
npm run dist:mac              # .dmg/.zip   (run on macOS)
npm run dist:linux            # .AppImage/.deb
```

Put icons in `electron/build/` first. Signing + auto-update feed: see
`electron/README.md`. CI: `.github/workflows/desktop-release.yml` (on `v*` tag).

## 4. Mobile app (Flutter) — `mobile/`

```bash
cd mobile
flutter create . --org com.noovapos --project-name noovapos_mobile   # one-time: android/ios shells
flutter pub get
flutter run                   # on a connected device / emulator
```

Release:

```bash
flutter build apk --release        # build/app/outputs/flutter-apk/app-release.apk
flutter build appbundle --release  # .aab for Google Play
flutter build ipa --release        # iOS (macOS + Apple Developer account)
```

Add Android signing (`android/key.properties`) + optional Firebase config
(`google-services.json`, `GoogleService-Info.plist`) — see `mobile/README.md`
and `mobile/FIREBASE.md`. CI: `.github/workflows/mobile-release.yml`.

## 5. Tests

```bash
php artisan test                         # backend
npm run test                             # web (Vitest)
( cd mobile && flutter analyze && flutter test )
bash scripts/smoke.sh http://abcgroup.noovapos.local   # live API smoke
```

Full details + CI: `TESTING.md`.

---

## One-shot bring-up (backend + web)

```bash
composer install && php artisan migrate:fresh --seed && php artisan storage:link \
  && npm install && npm run fetch:face-models && npm run build && php artisan horizon
```

## Notes

- **HTTPS / localhost** required for WebAuthn (device-sensor attendance) and
  camera access — enable SSL for the domain in Laragon, or use `localhost`.
- Run the queue worker (`php artisan horizon`) when using `migrate:fresh` so
  tenant databases get provisioned.
- Build the web app in the OS you deploy from (Vite/rolldown native binding is
  per-platform).
