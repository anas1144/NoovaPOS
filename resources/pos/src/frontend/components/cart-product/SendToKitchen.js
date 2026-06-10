import React, { useEffect, useState } from "react";
import { Button, Modal } from "react-bootstrap-v5";
import { useDispatch } from "react-redux";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faUtensils } from "@fortawesome/free-solid-svg-icons";
import apiConfig from "../../../config/apiConfig";
import { apiBaseURL, toastType } from "../../../constants";
import { addToast } from "../../../store/action/toastAction";

/**
 * Restaurant POS action: send the current cart to the kitchen as a KOT.
 * The backend routes it to the logged-in waiter's kitchen and we print the KOT.
 */
const SendToKitchen = ({ updateProducts, setUpdateProducts }) => {
    const dispatch = useDispatch();
    const [show, setShow] = useState(false);
    const [tables, setTables] = useState([]);
    const [tableId, setTableId] = useState("");
    const [note, setNote] = useState("");
    const [sending, setSending] = useState(false);
    const [kitchenEnabled, setKitchenEnabled] = useState(false);

    useEffect(() => {
        // Only show this action when the Kitchen feature is enabled for the store.
        apiConfig
            .get(apiBaseURL.MY_FEATURES)
            .then((res) => setKitchenEnabled(!!res.data?.data?.kitchen))
            .catch(() => {});
    }, []);

    useEffect(() => {
        if (show) {
            apiConfig
                .get(apiBaseURL.RESTAURANT_TABLES)
                .then((res) => setTables(res.data?.data || []))
                .catch(() => {});
        }
    }, [show]);

    // Only send the DELTA — the quantity added since the last KOT for this line.
    // This lets a waiter hold a bill, add items later, and reprint a KOT for the
    // newly added items only (kitchen never re-cooks what it already has).
    const buildItems = () =>
        (updateProducts || [])
            .map((p) => {
                const total = Number(p.quantity) || 0;
                const sent = Number(p.kot_sent_qty) || 0;
                return {
                    product_id: p.id,
                    product_name: p.name || p?.attributes?.name || "Item",
                    quantity: total - sent,
                };
            })
            .filter((i) => i.quantity > 0);

    const printKot = (ticket, items) => {
        const w = window.open("", "_blank", "width=320,height=600");
        if (!w) return;
        const rows = items
            .map(
                (i) =>
                    `<tr><td style="text-align:left">${i.product_name}</td><td style="text-align:right">x${i.quantity}</td></tr>`
            )
            .join("");
        w.document.write(`
            <html><head><title>KOT ${ticket?.ticket_no || ""}</title></head>
            <body style="font-family:monospace;width:280px;padding:6px">
              <h3 style="text-align:center;margin:4px 0">KITCHEN ORDER</h3>
              <div>KOT: <b>${ticket?.ticket_no || ""}</b></div>
              <div>Time: ${new Date().toLocaleString()}</div>
              ${tableId ? `<div>Table: ${tables.find((t) => String(t.id) === String(tableId))?.name || tableId}</div>` : ""}
              <hr/>
              <table style="width:100%;font-size:13px">${rows}</table>
              ${note ? `<hr/><div>Note: ${note}</div>` : ""}
              <hr/><div style="text-align:center">*** Kitchen Copy ***</div>
              <script>window.print();</script>
            </body></html>`);
        w.document.close();
    };

    const send = () => {
        const items = buildItems();
        if (items.length === 0) {
            dispatch(addToast({ text: "Cart is empty", type: toastType.ERROR }));
            return;
        }
        setSending(true);
        apiConfig
            .post(apiBaseURL.RESTAURANT_KOTS, {
                table_id: tableId || null,
                order_type: "dine_in",
                note: note || null,
                items,
            })
            .then((res) => {
                const ticket = res.data?.data;
                dispatch(addToast({ text: "Sent to kitchen." }));
                printKot(ticket, items);
                // Mark each line's full quantity as sent, so a later Send only
                // dispatches items added after this point.
                if (typeof setUpdateProducts === "function") {
                    setUpdateProducts(
                        (updateProducts || []).map((p) => ({
                            ...p,
                            kot_sent_qty: Number(p.quantity) || 0,
                        }))
                    );
                }
                setShow(false);
                setNote("");
                setTableId("");
            })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to send", type: toastType.ERROR }))
            )
            .finally(() => setSending(false));
    };

    if (!kitchenEnabled) {
        return null;
    }

    return (
        <>
            <Button
                variant="warning"
                className="w-100 mb-2"
                onClick={() => setShow(true)}
            >
                <FontAwesomeIcon icon={faUtensils} className="me-2" />
                Send to Kitchen
            </Button>

            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Send order to kitchen</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <div className="mb-3">
                        <label className="form-label">Table (optional)</label>
                        <select
                            className="form-control"
                            value={tableId}
                            onChange={(e) => setTableId(e.target.value)}
                        >
                            <option value="">— No table (takeaway) —</option>
                            {tables.map((t) => (
                                <option key={t.id} value={t.id}>{t.name}</option>
                            ))}
                        </select>
                    </div>
                    <div className="mb-3">
                        <label className="form-label">Note (optional)</label>
                        <textarea
                            className="form-control"
                            rows={2}
                            value={note}
                            onChange={(e) => setNote(e.target.value)}
                            placeholder="e.g. no onions, extra spicy"
                        />
                    </div>
                    <div className="text-muted small">
                        {buildItems().length} item(s) will be sent to your kitchen.
                    </div>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="primary" disabled={sending} onClick={send}>
                        {sending ? "Sending…" : "Send & Print KOT"}
                    </Button>
                    <Button variant="secondary" onClick={() => setShow(false)}>Cancel</Button>
                </Modal.Footer>
            </Modal>
        </>
    );
};

export default SendToKitchen;
