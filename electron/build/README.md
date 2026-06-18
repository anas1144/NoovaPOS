# Build resources

electron-builder reads icons and platform resources from this folder.

Drop your app icons here before building installers:

| File         | Used by | Recommended |
|--------------|---------|-------------|
| `icon.ico`   | Windows | 256×256 (multi-size ICO) |
| `icon.icns`  | macOS   | 512×512 / 1024×1024 |
| `icon.png`   | Linux   | 512×512 PNG |

A single 1024×1024 `icon.png` placed here is enough — electron-builder can
generate the platform variants. Tools: `electron-icon-builder`, or
`https://www.electron.build/icons`.

`entitlements.mac.plist` is the hardened-runtime entitlements used when
signing/notarizing the macOS build (camera + network for face check-in,
scanning and network printers).
