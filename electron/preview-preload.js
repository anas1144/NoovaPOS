const { contextBridge, ipcRenderer } = require("electron");

contextBridge.exposeInMainWorld("noovaPreview", {
    getHtml: () => ipcRenderer.invoke("noova:previewHtml"),
    print: () => ipcRenderer.invoke("noova:previewPrint"),
    close: () => window.close(),
});
