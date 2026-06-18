# NoovaPOS Desktop (Electron)

A native desktop shell that wraps the existing NoovaPOS web POS and adds
counter hardware support (printing, cash drawer, scanner, customer display).
It reuses 100% of the web UI — no separate frontend.

## Run (development)

```bash
cd electron
npm install
npm start
```

On first launch a setup screen asks for your **Server URL** (the tenant's
NoovaPOS web address, e.g. `https://abcgroup.noovapos.local`). Change it later
from the **Server → Change server URL…** menu.

## What's here (E-CP1)

- `main.js` — app window, menu (server URL, kiosk, zoom, customer display),
  external-link handling, electron-store config.
- `preload.js` — safe `window.noova` bridge exposed to the web app
  (`isDesktop`, `app()`, `getServerUrl()`, `setServerUrl()`; `print/openDrawer/
  onScan` are stubs until E-CP2).
- `setup.html` — first-run server URL screen.

The web POS can feature-detect desktop capabilities via `window.noova` and fall
back to browser behaviour when a capability isn't available yet.

## Build installers

```bash
npm run dist:win     # NSIS .exe (Windows)
npm run dist:mac     # .dmg (macOS)
npm run dist:linux   # AppImage (Linux)
```

(Installers are produced by electron-builder; configured in `package.json`.)

## Printing & cash drawer (E-CP2)

Open **Hardware → Printer settings…** to configure a printer:

- **System printer** — any OS-installed printer (incl. USB). Prints a rendered
  HTML receipt silently. Works everywhere, no extra setup.
- **Network ESC/POS** — a thermal printer reachable by `IP:port` (e.g.
  `192.168.0.99:9100`). Uses raw ESC/POS via `node-thermal-printer` and supports
  the **cash-drawer kick**.

Use **Test print** and **Open drawer** on that screen to verify. The web POS can
print through the desktop by calling `window.noova.print(receipt)` and
`window.noova.openDrawer()` (feature-detect `window.noova?.isDesktop`); the
`receipt` shape is documented in `printer.js`.

## Offline, auto-update & customer display (E-CP3)

- **Offline** — the POS is a PWA, so once it has loaded online its service worker
  serves it from cache. If the server is unreachable, the app shows
  `offline.html` (sales keep working from cache and sync later) and auto-retries
  every few seconds.
- **Auto-update** — packaged builds check the release feed on launch
  (`electron-updater`), download in the background, and prompt to restart. Set
  the feed in `package.json` → `build.publish` (default placeholder
  `https://releases.noovapos.com/desktop/`).
- **Customer display** — **Display → Open customer display…** opens
  `/app/customer-displays`; if a second monitor is present it opens there
  fullscreen.

## Build & release installers (E-CP4)

Put your icons in `build/` (see `build/README.md`), then:

```bash
cd electron
npm install
npm run dist:win     # dist/NoovaPOS Setup x.y.z.exe  (NSIS)
npm run dist:mac     # dist/NoovaPOS-x.y.z.dmg + .zip
npm run dist:linux   # dist/NoovaPOS-x.y.z.AppImage + .deb
```

Output goes to `electron/dist/`.

### Code signing (optional but recommended)

- **Windows** — set `CSC_LINK` (path/base64 of your `.pfx`) and `CSC_KEY_PASSWORD`.
- **macOS** — set `APPLE_ID`, `APPLE_APP_SPECIFIC_PASSWORD`, `APPLE_TEAM_ID`;
  with `hardenedRuntime` + the entitlements file the `.dmg` is signed and
  notarized. Unsigned builds still run but warn on first open.

### Auto-update feed

`build.publish` points at `https://releases.noovapos.com/desktop/` — change it to
your host (generic server, S3, or GitHub Releases). `electron-builder --publish
always` uploads the installer **and** the `latest.yml` feed the in-app updater reads.

### CI

`.github/workflows/desktop-release.yml` builds all three platforms and publishes
on a `v*` tag push (add the signing secrets to the repo to sign in CI).

## Status

Desktop app complete (E-CP1…E-CP4). Next workstream: the **Flutter** mobile app.
