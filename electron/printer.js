/**
 * Receipt printing for NoovaPOS Desktop.
 *
 * Two paths, no fragile native modules required:
 *   - "network": ESC/POS over TCP via node-thermal-printer (ethernet/wifi/print-
 *     server thermal printers). Supports the cash-drawer kick.
 *   - "system":  any OS-installed printer (incl. USB) via Electron's silent HTML
 *     print (handled in main.js using buildReceiptHtml()).
 *
 * A `receipt` is a plain object:
 *   { title, subtitle, address, meta:[[k,v]], items:[{name,qty,price,amount}],
 *     totals:[[k,v]], footer, qr, openDrawer:bool }
 */

let ThermalPrinter, PrinterTypes;
try {
    ({ ThermalPrinter, printers: PrinterTypes } = require("node-thermal-printer"));
} catch (e) {
    // Dependency not installed yet — network printing will report a clear error.
}

function makePrinter(config) {
    if (!ThermalPrinter) {
        throw new Error("node-thermal-printer is not installed. Run `npm install` in the electron folder.");
    }
    const addr = String(config.address || "").trim();
    const iface = addr.startsWith("tcp://") ? addr : `tcp://${addr}`;
    return new ThermalPrinter({
        type: PrinterTypes.EPSON,
        interface: iface,
        width: Number(config.width) || 48,
        characterSet: config.charset || "PC852_LATIN2",
        removeSpecialCharacters: false,
        options: { timeout: 4000 },
    });
}

async function networkPrint(config, receipt) {
    const printer = makePrinter(config);
    renderEscPos(printer, receipt);
    if (receipt.openDrawer) printer.openCashDrawer();
    printer.cut();
    await printer.execute();
    return { ok: true };
}

async function openDrawer(config) {
    const printer = makePrinter(config);
    printer.openCashDrawer();
    await printer.execute();
    return { ok: true };
}

function renderEscPos(printer, r) {
    printer.alignCenter();
    if (r.title) { printer.bold(true); printer.setTextDoubleHeight(); printer.println(r.title); printer.setTextNormal(); printer.bold(false); }
    if (r.subtitle) printer.println(r.subtitle);
    if (r.address) printer.println(r.address);
    printer.drawLine();

    printer.alignLeft();
    (r.meta || []).forEach(([k, v]) => printer.leftRight(String(k), String(v)));
    if ((r.meta || []).length) printer.drawLine();

    (r.items || []).forEach((it) => {
        printer.leftRight(`${it.name}`, `${it.amount ?? it.price}`);
        if (it.qty != null) printer.println(`   ${it.qty} x ${it.price}`);
    });
    printer.drawLine();

    (r.totals || []).forEach(([k, v]) => printer.leftRight(String(k), String(v)));

    if (r.qr) { printer.alignCenter(); printer.printQR(String(r.qr), { cellSize: 6 }); }
    if (r.footer) { printer.alignCenter(); printer.newLine(); printer.println(r.footer); }
    printer.newLine();
}

/** HTML used for the "system" print path and the test page. */
function buildReceiptHtml(r) {
    const rows = (r.items || []).map((it) =>
        `<tr><td>${esc(it.name)}${it.qty != null ? `<div class="sub">${it.qty} x ${it.price}</div>` : ""}</td>` +
        `<td class="r">${esc(it.amount ?? it.price)}</td></tr>`).join("");
    const totals = (r.totals || []).map(([k, v]) =>
        `<tr><td>${esc(k)}</td><td class="r">${esc(v)}</td></tr>`).join("");
    const meta = (r.meta || []).map(([k, v]) =>
        `<div class="meta"><span>${esc(k)}</span><span>${esc(v)}</span></div>`).join("");

    return `<!DOCTYPE html><html><head><meta charset="utf-8"><style>
        * { font-family: monospace; }
        body { width: 280px; margin: 0 auto; color: #000; }
        h2 { text-align:center; margin: 4px 0; }
        .c { text-align:center; }
        .meta { display:flex; justify-content:space-between; font-size:12px; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        td { vertical-align:top; padding:2px 0; }
        .r { text-align:right; white-space:nowrap; }
        .sub { color:#444; font-size:11px; }
        hr { border:0; border-top:1px dashed #000; }
    </style></head><body>
        ${r.title ? `<h2>${esc(r.title)}</h2>` : ""}
        ${r.subtitle ? `<div class="c">${esc(r.subtitle)}</div>` : ""}
        ${r.address ? `<div class="c">${esc(r.address)}</div>` : ""}
        <hr/>${meta}${meta ? "<hr/>" : ""}
        <table>${rows}</table><hr/>
        <table>${totals}</table>
        ${r.footer ? `<hr/><div class="c">${esc(r.footer)}</div>` : ""}
    </body></html>`;
}

function esc(s) {
    return String(s == null ? "" : s).replace(/[&<>]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;" }[c]));
}

module.exports = { networkPrint, openDrawer, buildReceiptHtml };
