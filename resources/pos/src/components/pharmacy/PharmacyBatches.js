import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Button, Modal } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const blank = { product_id: "", batch_no: "", mfg_date: "", expiry_date: "", quantity: "0", cost: "0", status: "active" };

const PharmacyBatches = () => {
    const dispatch = useDispatch();
    const [rows, setRows] = useState([]);
    const [alerts, setAlerts] = useState([]);
    const [products, setProducts] = useState([]);
    const [show, setShow] = useState(false);
    const [form, setForm] = useState(blank);
    const [editing, setEditing] = useState(null);
    const [saving, setSaving] = useState(false);

    const toast = (text, type) => dispatch(addToast({ text, type }));

    const load = () => apiConfig.get(apiBaseURL.PRODUCT_BATCHES).then((r) => setRows(r.data?.data?.data || r.data?.data || [])).catch(() => {});
    const loadAlerts = () => apiConfig.get(apiBaseURL.PRODUCT_BATCHES_NEAR_EXPIRY).then((r) => setAlerts(r.data?.data || [])).catch(() => {});
    const loadProducts = () => apiConfig.get(apiBaseURL.PRODUCTS_LIST, { params: { page_size: 0 } })
        .then((r) => setProducts(r.data?.data?.data || r.data?.data || [])).catch(() => {});
    useEffect(() => { load(); loadAlerts(); loadProducts(); }, []);

    const open = (row) => {
        setEditing(row?.id || null);
        setForm(row ? {
            ...blank, ...row,
            mfg_date: (row.mfg_date || "").slice(0, 10),
            expiry_date: (row.expiry_date || "").slice(0, 10),
            quantity: String(row.quantity), cost: String(row.cost),
        } : blank);
        setShow(true);
    };
    const setF = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));

    const save = () => {
        if (!form.product_id) { toast("Select a product", toastType.ERROR); return; }
        setSaving(true);
        const body = { ...form };
        Object.keys(body).forEach((k) => { if (body[k] === "") delete body[k]; });
        const req = editing
            ? apiConfig.patch(`${apiBaseURL.PRODUCT_BATCHES}/${editing}`, body)
            : apiConfig.post(apiBaseURL.PRODUCT_BATCHES, body);
        req.then(() => { toast("Batch saved."); setShow(false); load(); loadAlerts(); })
            .catch(({ response }) => toast(response?.data?.message || "Save failed", toastType.ERROR))
            .finally(() => setSaving(false));
    };

    const remove = (row) => {
        apiConfig.delete(`${apiBaseURL.PRODUCT_BATCHES}/${row.id}`)
            .then(() => { toast("Batch removed."); load(); loadAlerts(); })
            .catch(() => toast("Delete failed", toastType.ERROR));
    };

    const pName = (p) => `${p.name || p.attributes?.name || ""}${(p.code || p.attributes?.code) ? ` (${p.code || p.attributes?.code})` : ""}`;

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Product Batches & Expiry" />

            {alerts.length > 0 && (
                <div className="alert alert-light-warning">
                    <strong>{alerts.length} batch(es) expiring soon / expired.</strong>
                    <div className="table-responsive mt-2">
                        <table className="table table-sm align-middle mb-0">
                            <thead><tr><th>Product</th><th>Batch</th><th>Expiry</th><th>Days left</th><th>Qty</th></tr></thead>
                            <tbody>
                                {alerts.slice(0, 8).map((a) => (
                                    <tr key={a.id} className={a.expired ? "text-danger" : ""}>
                                        <td>{a.product}</td><td>{a.batch_no}</td><td>{a.expiry_date}</td>
                                        <td>{a.expired ? "Expired" : `${a.days_left}d`}</td><td>{a.quantity}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            <div className="card">
                <div className="card-header d-flex justify-content-between align-items-center">
                    <h6 className="mb-0">Batches</h6>
                    <Button variant="primary" onClick={() => open(null)}>+ Add batch</Button>
                </div>
                <div className="card-body table-responsive">
                    <table className="table align-middle">
                        <thead><tr><th>Product</th><th>Batch</th><th>Mfg</th><th>Expiry</th><th>Qty</th><th>Cost</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            {rows.length === 0 ? (
                                <tr><td colSpan={8} className="text-center text-muted py-4">No batches.</td></tr>
                            ) : rows.map((r) => (
                                <tr key={r.id}>
                                    <td>{r.product?.name || r.product_id}</td>
                                    <td>{r.batch_no}</td>
                                    <td>{r.mfg_date ? new Date(r.mfg_date).toLocaleDateString() : "—"}</td>
                                    <td>{r.expiry_date ? new Date(r.expiry_date).toLocaleDateString() : "—"}</td>
                                    <td>{r.quantity}</td><td>{r.cost}</td>
                                    <td className="text-capitalize">{r.status}</td>
                                    <td className="text-end">
                                        <button className="btn btn-sm btn-link" onClick={() => open(r)}>Edit</button>
                                        <button className="btn btn-sm btn-link text-danger" onClick={() => remove(r)}>Delete</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton><Modal.Title>{editing ? "Edit" : "Add"} batch</Modal.Title></Modal.Header>
                <Modal.Body>
                    <div className="mb-3"><label className="form-label">Product *</label>
                        <select className="form-control" value={form.product_id} onChange={setF("product_id")}>
                            <option value="">— Select product —</option>
                            {products.map((p) => <option key={p.id} value={p.id}>{pName(p)}</option>)}
                        </select></div>
                    <div className="row">
                        <div className="col-6 mb-3"><label className="form-label">Batch no. *</label>
                            <input className="form-control" value={form.batch_no} onChange={setF("batch_no")} /></div>
                        <div className="col-6 mb-3"><label className="form-label">Status</label>
                            <select className="form-control" value={form.status} onChange={setF("status")}>
                                <option value="active">Active</option><option value="quarantined">Quarantined</option><option value="expired">Expired</option>
                            </select></div>
                        <div className="col-6 mb-3"><label className="form-label">Mfg date</label>
                            <input type="date" className="form-control" value={form.mfg_date} onChange={setF("mfg_date")} /></div>
                        <div className="col-6 mb-3"><label className="form-label">Expiry date</label>
                            <input type="date" className="form-control" value={form.expiry_date} onChange={setF("expiry_date")} /></div>
                        <div className="col-6 mb-3"><label className="form-label">Quantity *</label>
                            <input type="number" className="form-control" value={form.quantity} onChange={setF("quantity")} /></div>
                        <div className="col-6 mb-3"><label className="form-label">Cost</label>
                            <input type="number" className="form-control" value={form.cost} onChange={setF("cost")} /></div>
                    </div>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="primary" disabled={saving} onClick={save}>{saving ? "Saving…" : "Save"}</Button>
                    <Button variant="secondary" onClick={() => setShow(false)}>Close</Button>
                </Modal.Footer>
            </Modal>
        </MasterLayout>
    );
};

export default PharmacyBatches;
