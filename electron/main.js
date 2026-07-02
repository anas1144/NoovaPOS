/**
 * NoovaPOS Desktop — Electron main process.
 *
 * Wraps the existing web POS in a native window and adds the hooks a counter
 * needs (printing, cash drawer, scanner — filled in over the next checkpoints).
 *
 * The server URL is stored with electron-store; on first run a small setup page
 * asks for it. Use the app menu (Server → Change server URL…) to change it.
 */
const { app, BrowserWindow, Menu, ipcMain, dialog, shell, screen } = require("electron");
const path = require("path");
const Store = require("electron-store");
const printer = require("./printer");

let autoUpdater = null;
try { ({ autoUpdater } = require("electron-updater")); } catch (e) { /* not installed yet */ }

const store = new Store({
    defaults: {
        serverUrl: "",
        kiosk: false,
        zoom: 1,
        printer: { type: "system", name: "", address: "", width: 48, charset: "PC852_LATIN2" },
    },
});

let mainWindow = null;
let customerWindow = null;
let settingsWindow = null;
let previewWindow = null;
let pendingReceiptHtml = "";

/** Print raw receipt HTML silently to the configured/default printer. */
function silentPrintHtml(html) {
    const cfg = store.get("printer") || {};
    return new Promise((resolve) => {
        let done = false;
        const finish = (res) => { if (!done) { done = true; resolve(res); } };
        const w = new BrowserWindow({ show: false, webPreferences: { offscreen: false } });
        // Never hang — if printing throws or the spooler is down, report it.
        const guard = setTimeout(() => { try { w.close(); } catch (e) {} finish({ ok: false, error: "Print timed out (is the Windows Print Spooler running?)" }); }, 20000);
        w.webContents.once("did-finish-load", () => {
            try {
                w.webContents.print(
                    { silent: true, deviceName: cfg.name || undefined, margins: { marginType: "none" } },
                    (ok, err) => { clearTimeout(guard); try { w.close(); } catch (e) {} finish({ ok, error: ok ? null : (err || "Print failed") }); }
                );
            } catch (e) {
                clearTimeout(guard);
                try { w.close(); } catch (_) {}
                finish({ ok: false, error: String(e.message || e) + " — check the Windows Print Spooler service." });
            }
        });
    });
}

/** Show the receipt in a small preview window with Print / Close buttons. */
function openReceiptPreview(html) {
    pendingReceiptHtml = html;
    if (previewWindow) { previewWindow.focus(); previewWindow.webContents.reload(); return; }
    previewWindow = new BrowserWindow({
        width: 340, height: 620, title: "Receipt preview", parent: mainWindow,
        webPreferences: { preload: path.join(__dirname, "preview-preload.js"), contextIsolation: true },
    });
    previewWindow.setMenuBarVisibility(false);
    previewWindow.loadFile(path.join(__dirname, "preview.html"));
    previewWindow.on("closed", () => { previewWindow = null; });
}

// JS injected into every frame: replace window.print() with a call to our
// bridge so it routes to printHtml (silent or preview), instead of the OS dialog.
const PRINT_OVERRIDE_JS = `
(function () {
    if (window.__noovaPrintPatched) return;
    window.__noovaPrintPatched = true;
    var orig = window.print ? window.print.bind(window) : null;
    function bridge() {
        try { if (window.noova && window.noova.printHtml) return window.noova; } catch (e) {}
        try { if (window.top && window.top.noova && window.top.noova.printHtml) return window.top.noova; } catch (e) {}
        return null;
    }
    window.print = function () {
        var n = bridge();
        try {
            if (n) { n.printHtml('<!DOCTYPE html>' + document.documentElement.outerHTML); return; }
        } catch (e) {}
        if (orig) orig();
    };
})();
`;

/** Inject the print override into the main frame and every sub-frame (iframes). */
function patchPrintInAllFrames(wc) {
    try {
        const frames = (wc && wc.mainFrame && wc.mainFrame.framesInSubtree) || [];
        for (const f of frames) {
            try { f.executeJavaScript(PRINT_OVERRIDE_JS, true).catch(() => {}); } catch (e) {}
        }
    } catch (e) { /* ignore */ }
}

/** True if two URLs share the same origin (protocol + host + port). */
function _sameOrigin(a, b) {
    try {
        if (!a || !b) return false;
        const ua = new URL(a);
        const ub = new URL(b);
        return ua.origin === ub.origin;
    } catch (e) {
        return false;
    }
}

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1280,
        height: 800,
        minWidth: 1024,
        minHeight: 680,
        backgroundColor: "#0f172a",
        kiosk: store.get("kiosk"),
        webPreferences: {
            preload: path.join(__dirname, "preload.js"),
            contextIsolation: true,
            nodeIntegration: false,
            spellcheck: false,
        },
    });

    const url = store.get("serverUrl");
    if (url) {
        mainWindow.loadURL(url);
    } else {
        mainWindow.loadFile(path.join(__dirname, "setup.html"));
    }

    mainWindow.webContents.setZoomFactor(Number(store.get("zoom")) || 1);

    // If the store URL can't be reached, show the offline page (sales keep
    // working from the PWA cache; it auto-retries the connection).
    mainWindow.webContents.on("did-fail-load", (_e, errorCode, _desc, validatedURL, isMainFrame) => {
        if (!isMainFrame) return;
        if (errorCode === -3) return; // aborted (navigation), ignore
        const onAppPage = validatedURL && store.get("serverUrl") && validatedURL.startsWith(store.get("serverUrl"));
        if (onAppPage) {
            mainWindow.loadFile(path.join(__dirname, "offline.html"));
        }
    });

    // Intercept window.print() for ANY loaded site (and any iframe, e.g.
    // react-to-print) and route it through our printHtml → silent print or
    // preview, per Printer settings. Patched into every frame on load.
    const patchAll = () => patchPrintInAllFrames(mainWindow.webContents);
    mainWindow.webContents.on("dom-ready", patchAll);
    mainWindow.webContents.on("did-frame-finish-load", patchAll);
    mainWindow.webContents.on("frame-created", patchAll);

    // Link handling for target="_blank" / window.open:
    //  - same-origin (your store) → open INSIDE the app (a child window).
    //  - a different http(s) host → open in the OS browser.
    //  - non-http (about:blank popups used for receipt/KOT printing, mailto, etc.)
    //    → allow as a normal in-app window.
    mainWindow.webContents.setWindowOpenHandler(({ url: target }) => {
        if (!/^https?:/i.test(target)) {
            return { action: "allow" };
        }
        if (_sameOrigin(target, store.get("serverUrl"))) {
            return { action: "allow" }; // in-app page (pay link, popups, etc.)
        }
        shell.openExternal(target);      // truly external site
        return { action: "deny" };
    });

    mainWindow.on("closed", () => { mainWindow = null; });
}

function setServerUrlInteractive() {
    // Reload setup page so the user can (re)enter the URL.
    store.set("serverUrl", "");
    if (mainWindow) mainWindow.loadFile(path.join(__dirname, "setup.html"));
}

function buildMenu() {
    const template = [
        {
            label: "Server",
            submenu: [
                { label: "Change server URL…", click: setServerUrlInteractive },
                { label: "Reload", accelerator: "CmdOrCtrl+R", click: () => mainWindow && mainWindow.reload() },
                { type: "separator" },
                {
                    label: "Toggle kiosk mode",
                    type: "checkbox",
                    checked: store.get("kiosk"),
                    click: (item) => { store.set("kiosk", item.checked); if (mainWindow) mainWindow.setKiosk(item.checked); },
                },
                { role: "quit" },
            ],
        },
        {
            label: "View",
            submenu: [
                { label: "Zoom In", accelerator: "CmdOrCtrl+=", click: () => zoom(0.1) },
                { label: "Zoom Out", accelerator: "CmdOrCtrl+-", click: () => zoom(-0.1) },
                { label: "Reset Zoom", accelerator: "CmdOrCtrl+0", click: () => zoom(0, true) },
                { type: "separator" },
                { role: "togglefullscreen" },
                { role: "toggleDevTools" },
            ],
        },
        {
            label: "Display",
            submenu: [
                { label: "Open customer display…", click: openCustomerDisplay },
            ],
        },
        {
            label: "Hardware",
            submenu: [
                { label: "Printer settings…", click: openPrinterSettings },
                { label: "Open cash drawer", click: drawerKick },
            ],
        },
    ];
    Menu.setApplicationMenu(Menu.buildFromTemplate(template));
}

function zoom(delta, reset = false) {
    if (!mainWindow) return;
    const z = reset ? 1 : Math.max(0.5, Math.min(2.5, (Number(store.get("zoom")) || 1) + delta));
    store.set("zoom", z);
    mainWindow.webContents.setZoomFactor(z);
}

/** Second monitor customer display — opens on an external display if present. */
function openCustomerDisplay() {
    const base = store.get("serverUrl");
    if (!base) { dialog.showMessageBox({ message: "Set the server URL first." }); return; }
    if (customerWindow) { customerWindow.focus(); return; }

    // Prefer a secondary display (not the one the main window is on).
    const displays = screen.getAllDisplays();
    const primary = screen.getPrimaryDisplay();
    const external = displays.find((d) => d.id !== primary.id);
    const opts = { width: 1024, height: 768, title: "Customer Display" };
    if (external) { opts.x = external.bounds.x + 40; opts.y = external.bounds.y + 40; opts.fullscreen = true; }

    customerWindow = new BrowserWindow(opts);
    customerWindow.loadURL(base.replace(/\/$/, "") + "/app/customer-displays");
    customerWindow.on("closed", () => { customerWindow = null; });
}

/** Check for updates (packaged builds only; configured via the publish feed). */
function initAutoUpdate() {
    if (!autoUpdater || !app.isPackaged) return;
    try {
        autoUpdater.autoDownload = true;
        autoUpdater.on("update-downloaded", () => {
            dialog.showMessageBox({
                type: "info",
                buttons: ["Restart now", "Later"],
                message: "A NoovaPOS update was downloaded. Restart to apply it?",
            }).then((res) => { if (res.response === 0) autoUpdater.quitAndInstall(); });
        });
        autoUpdater.checkForUpdatesAndNotify();
    } catch (e) { /* offline / no feed — ignore */ }
}

/** Printer settings window. */
function openPrinterSettings() {
    if (settingsWindow) { settingsWindow.focus(); return; }
    settingsWindow = new BrowserWindow({
        width: 480, height: 560, title: "Printer settings", parent: mainWindow, modal: false,
        webPreferences: { preload: path.join(__dirname, "preload.js"), contextIsolation: true },
    });
    settingsWindow.setMenuBarVisibility(false);
    settingsWindow.loadFile(path.join(__dirname, "settings.html"));
    settingsWindow.on("closed", () => { settingsWindow = null; });
}

/** Cash-drawer kick from the menu (network ESC/POS printers only). */
async function drawerKick() {
    const cfg = store.get("printer") || {};
    if (cfg.type !== "network") {
        dialog.showMessageBox({ message: "Cash drawer requires a network ESC/POS printer (configure it in Printer settings)." });
        return;
    }
    try { await printer.openDrawer(cfg); }
    catch (e) { dialog.showMessageBox({ message: "Cash drawer failed: " + (e.message || e) }); }
}

/** Print a structured receipt to a system printer — preview first if enabled. */
function systemPrint(config, receipt) {
    const html = printer.buildReceiptHtml(receipt);
    if (config.preview) {
        openReceiptPreview(html);
        return Promise.resolve({ ok: true, previewed: true });
    }
    return silentPrintHtml(html);
}

// ── IPC from the renderer (preload bridge) ───────────────────────────────────
ipcMain.handle("noova:getServerUrl", () => store.get("serverUrl"));
ipcMain.handle("noova:getPrinters", async (event) => {
    // Enumerate from the window that asked (the settings window is loaded and
    // reliable); fall back to the main window.
    const sources = [event.sender, mainWindow && mainWindow.webContents].filter(Boolean);
    for (const wc of sources) {
        try {
            const list = await wc.getPrintersAsync();
            if (Array.isArray(list) && list.length) return list;
        } catch (e) { /* try next */ }
    }
    return [];
});
ipcMain.handle("noova:getPrinterConfig", () => store.get("printer"));
ipcMain.handle("noova:setPrinterConfig", (_e, cfg) => { store.set("printer", cfg || {}); return { ok: true }; });
ipcMain.handle("noova:print", async (_e, receipt) => {
    const cfg = store.get("printer") || {};
    try {
        if (cfg.type === "network") return await printer.networkPrint(cfg, receipt || {});
        return await systemPrint(cfg, receipt || {});
    } catch (e) { return { ok: false, error: String(e.message || e) }; }
});
ipcMain.handle("noova:openDrawer", async () => {
    const cfg = store.get("printer") || {};
    try {
        if (cfg.type === "network") return await printer.openDrawer(cfg);
        return { ok: false, error: "Cash drawer requires a network ESC/POS printer." };
    } catch (e) { return { ok: false, error: String(e.message || e) }; }
});
ipcMain.handle("noova:openPrinterSettings", () => { openPrinterSettings(); return { ok: true }; });
// Silent print of raw HTML (used by the web POS receipt via react-to-print) —
// no OS dialog, straight to the configured/default printer. If "Preview before
// print" is enabled in printer settings, a preview window is shown first.
ipcMain.handle("noova:printHtml", async (_e, html) => {
    const cfg = store.get("printer") || {};
    if (cfg.preview) {
        openReceiptPreview(String(html || ""));
        return { ok: true, previewed: true };
    }
    return silentPrintHtml(String(html || ""));
});

ipcMain.handle("noova:previewHtml", () => pendingReceiptHtml);
ipcMain.handle("noova:previewPrint", async () => {
    const r = await silentPrintHtml(pendingReceiptHtml);
    if (previewWindow) previewWindow.close();
    return r;
});
ipcMain.handle("noova:setServerUrl", (_e, url) => {
    const clean = String(url || "").trim().replace(/\/$/, "");
    if (!/^https?:\/\//.test(clean)) return { ok: false, error: "Enter a full http(s) URL." };
    store.set("serverUrl", clean);
    if (mainWindow) mainWindow.loadURL(clean);
    return { ok: true };
});
ipcMain.handle("noova:app", () => ({ name: "NoovaPOS Desktop", version: app.getVersion() }));
// Printing / cash drawer / scanner handlers are added in E-CP2.

app.whenReady().then(() => {
    buildMenu();
    createWindow();
    initAutoUpdate();
    app.on("activate", () => { if (BrowserWindow.getAllWindows().length === 0) createWindow(); });
});

app.on("window-all-closed", () => { if (process.platform !== "darwin") app.quit(); });
