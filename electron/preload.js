/**
 * Preload bridge — exposes a safe `window.noova` API to the web POS without
 * enabling nodeIntegration. Native capabilities (print/drawer/scan) are added to
 * this object in later checkpoints; the renderer can feature-detect them.
 */
const { contextBridge, ipcRenderer } = require("electron");

contextBridge.exposeInMainWorld("noova", {
    isDesktop: true,
    app: () => ipcRenderer.invoke("noova:app"),
    getServerUrl: () => ipcRenderer.invoke("noova:getServerUrl"),
    setServerUrl: (url) => ipcRenderer.invoke("noova:setServerUrl", url),

    // Receipt printing + cash drawer (E-CP2).
    print: (receipt) => ipcRenderer.invoke("noova:print", receipt),
    printHtml: (html) => ipcRenderer.invoke("noova:printHtml", html),
    openDrawer: () => ipcRenderer.invoke("noova:openDrawer"),
    getPrinters: () => ipcRenderer.invoke("noova:getPrinters"),
    getPrinterConfig: () => ipcRenderer.invoke("noova:getPrinterConfig"),
    setPrinterConfig: (cfg) => ipcRenderer.invoke("noova:setPrinterConfig", cfg),
    openPrinterSettings: () => ipcRenderer.invoke("noova:openPrinterSettings"),

    // Scanner stream wired in a later checkpoint.
    onScan: () => () => {},
});
