# Why Firebase (FCM) in the NoovaPOS mobile app

Short answer: **only for push notifications**, and it's **optional** — the app
runs fully without it.

## What Firebase is used for

Exactly one thing: **Firebase Cloud Messaging (FCM)** — delivering push
notifications to the phone (e.g. "new delivery assigned", "KOT ready",
"payment confirmed", low-stock alerts).

It is **not** used for:

- Authentication — that's Laravel Sanctum (`POST /api/login`).
- Database / storage — that's the Laravel API + local SQLite cache.
- Analytics, crash reporting, hosting, or anything else.

Only two packages are added: `firebase_core` (bootstrap) and
`firebase_messaging` (FCM). See `lib/core/push.dart`.

## Why FCM specifically

Mobile OSes do not let an app reliably receive background/locked-screen
notifications on its own — you must go through the platform's push service:

- **Android** → Firebase Cloud Messaging (FCM) is *the* Google-provided channel.
- **iOS** → Apple Push Notification service (APNs); FCM is the standard way
  Flutter apps talk to APNs too, so one integration covers both platforms.

So for cross-platform push from a Flutter app, FCM is the de-facto standard.

### Why not something else?

| Option | Why not (for this app) |
|---|---|
| Polling the API | Drains battery, no notifications when the app is closed/locked. |
| WebSockets only | Doesn't wake a backgrounded/terminated app; OS kills the socket. |
| OneSignal / Pusher Beams | Fine, but they sit **on top of** FCM/APNs anyway and add a third-party account + cost; FCM direct is simpler and free. |
| Self-hosted push | Not possible — Android/iOS require their official push gateways. |

## Cost

**FCM is free** with no message quotas for standard notifications, which fits
the project's "use free methods" rule. The only requirement is a (free) Firebase
project to get the credentials.

## It's optional — the app works without it

`PushService` and `Firebase.initializeApp()` are wrapped in try/catch. If the
Firebase config files aren't present, the app **skips push and runs normally** —
login, POS, offline sales, printing, scanning and all role screens are
unaffected. Push only activates once you add:

- `android/app/google-services.json`
- `ios/Runner/GoogleService-Info.plist`

(see `README.md` → *Push notifications & release*).

## How it fits the backend

The app registers its FCM token with the existing endpoint
`POST /api/v1/devices` (stored in the `device_tokens` table). The server can then
send a notification to a user's devices through FCM when an event happens — no
new client dependency beyond the two packages above.

## Removing Firebase entirely

If you prefer zero Firebase: delete `firebase_core` + `firebase_messaging` from
`pubspec.yaml`, remove `lib/core/push.dart` and its calls in `main.dart` /
`home_screen.dart`. Everything else keeps working; you simply lose push
notifications.
