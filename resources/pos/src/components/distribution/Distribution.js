import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Button, Modal } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const today = () => new Date().toISOString().slice(0, 10);
const emptyItem = () => ({ product_id: "", loaded_qty: "" });

const badge = (s) => ({ loaded: "bg-light-warning", out: "bg-light-info", reconciled: "bg-light-success" }[s] || "bg-light-secondary");

const Distribution = () => {
    const dispatch = useDispatch();
    const [loads, setLoads] = useState([]);
    const [routes, setRoutes] = useState([]);
    const [products, setProducts] = useState([]);

    const [show, setShow] = useState(false);
    const [form, setForm] = useState({ delivery_route_id: "", date: today(), vehicle: "", note: "", items: [emptyItem()] });

    const [recon, setRecon] = useState(null); // load being reconciled
    const [reconItems, setReconItems] = useState([]);

    const toast = (text, type) => dispatch(addToast({ text, type }));
    const arr = (r) => r.data?.data?.data || r.data?.data || [];

    const loadLoads = () => apiConfig.get(apiBaseURL.DISTRIBUTION_LOADS).then((r) => setLoads(arr(r))).catch(() => {});
    const loadRoutes = () => apiConfig.get(apiBaseURL.DISTRIBUTION_ROUTES).then((r) => setRoutes(r.data?.data || [])).catch(() => {});
    const loadProducts = () => apiConfig.get(apiBaseURL.PRODUCTS_LIST, { params: { page_size: 0 } }).then((r) => setProducts(arr(r))).catch(() => {});
    useEffect(() => { loadLoads(); loadRoutes(); loadProducts(); }, []);

    const pName = (p) => `${p.name || p.attributes?.name || `#${p.id}`}`;
    const prodName = (id) => { const p = products.find((x) => String(x.id) === String(id)); return p ? pName(p) : id; };

    const setItem = (i, k) => (e) => setForm((f) => ({ ...f, items: f.items.map((it, idx) => idx === i ? { ...it, [k]: e.target.value } : it) }));
    const addItem = () => setForm((f) => ({ ...f, items: [...f.items, emptyItem()] }));
    const removeItem = (i) => setForm((f) => ({ ...f, items: f.items.length > 1 ? f.items.filter((_, idx) => idx !== i) : f.items }));

    const createLoad = () => {
        const items = form.items.filter((it) => it.product_id && it.loaded_qty !== "");
        if (items.length === 0) { toast("Add at least one product", toastType.ERROR); return; }
        apiConfig.post(apiBaseURL.DISTRIBUTION_LOADS, { ...form, items })
            .then(() => { toast("Load created."); setShow(false); setForm({ delivery_route_id: "", date: today(), vehicle: "", note: "", items: [emptyItem()] }); loadLoads(); })
            .catch(({ response }) => toast(response?.data?.message || "Failed", toastType.ERROR));
    };

    const openRecon = (load) => {
        setRecon(load);
        setReconItems((load.items || []).map((it) => ({
            id: it.id, product_id: it.product_id, loaded_qty: it.loaded_qty,
            delivered_qty: String(it.delivered_qty ?? it.loaded_qty), returned_qty: String(it.returned_qty ?? 0),
        })));
    };
    const setRecField = (i, k) => (e) => setReconItems((rows) => rows.map((r, idx) => idx === i ? { ...r, [k]: e.target.value } : r));

    const submitRecon = () => {
        apiConfig.post(`${apiBaseURL.DISTRIBUTION_LOADS}/${recon.id}/reconcile`, {
            items: reconItems.map((r) => ({ id: r.id, delivered_qty: Number(r.delivered_qty) || 0, returned_qty: Number(r.returned_qty) || 0 })),
        }).then(() => { toast("Reconciled."); setRecon(null); loadLoads(); })
            .catch(({ response }) => toast(response?.data?.message || "Failed", toastType.ERROR));
    };

    const delLoad = (l) => apiConfig.delete(`${apiBaseURL.DISTRIBUTION_LOADS}/${l.id}`).then(() => { toast("Removed."); loadLoads(); }).catch(() => {});

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Distribution" />

            <div className="card">
                <div className="card-header d-flex justify-content-between align-items-center">
                    <h6 className="mb-0">Van loads / dispatch</h6>
                    <Button variant="primary" onClick={() => setShow(true)}>+ New load</Button>
                </div>
                <div className="card-body table-responsive">
                    <table className="table align-middle">
                        <thead><tr><th>#</th><th>Date</th><th>Route</th><th>Vehicle</th><th>Items</th><th>Status</th><th className="text-end">Actions</th></tr></thead>
                        <tbody>
                            {loads.length === 0 ? <tr><td colSpan={7} className="text-center text-muted py-4">No loads.</td></tr>
                                : loads.map((l) => (<tr key={l.id}>
                                    <td>{l.id}</td><td>{l.date ? new Date(l.date).toLocaleDateString() : "—"}</td>
                                    <td>{l.route?.name || "—"}</td><td>{l.vehicle || "—"}</td>
                                    <td>{(l.items || []).length}</td>
                                    <td><span className={`badge ${badge(l.status)} text-capitalize`}>{l.status}</span></td>
                                    <td className="text-end text-nowrap">
                                        {l.status !== "reconciled" && <button className="btn btn-sm btn-link" onClick={() => openRecon(l)}>Reconcile</button>}
                                        <button className="btn btn-sm btn-link text-danger" onClick={() => delLoad(l)}>Delete</button>
                                    </td>
                                </tr>))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Create load */}
            <Modal show={show} onHide={() => setShow(false)} size="lg">
                <Modal.Header closeButton><Modal.Title>New van load</Modal.Title></Modal.Header>
                <Modal.Body>
                    <div className="row">
                        <div className="col-md-4 mb-3"><label className="form-label">Route</label>
                            <select className="form-control" value={form.delivery_route_id} onChange={(e) => setForm((f) => ({ ...f, delivery_route_id: e.target.value }))}>
                                <option value="">—</option>{routes.map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
                            </select></div>
                        <div className="col-md-4 mb-3"><label className="form-label">Date</label>
                            <input type="date" className="form-control" value={form.date} onChange={(e) => setForm((f) => ({ ...f, date: e.target.value }))} /></div>
                        <div className="col-md-4 mb-3"><label className="form-label">Vehicle</label>
                            <input className="form-control" value={form.vehicle} onChange={(e) => setForm((f) => ({ ...f, vehicle: e.target.value }))} /></div>
                    </div>
                    <div className="d-flex justify-content-between align-items-center mb-2">
                        <h6 className="mb-0">Load items</h6><Button size="sm" variant="outline-primary" onClick={addItem}>+ Add</Button>
                    </div>
                    {form.items.map((it, i) => (
                        <div className="row g-2 mb-2 align-items-center" key={i}>
                            <div className="col-md-8"><select className="form-control" value={it.product_id} onChange={setItem(i, "product_id")}>
                                <option value="">— Product —</option>{products.map((p) => <option key={p.id} value={p.id}>{pName(p)}</option>)}
                            </select></div>
                            <div className="col-md-3"><input type="number" className="form-control" placeholder="Loaded qty" value={it.loaded_qty} onChange={setItem(i, "loaded_qty")} /></div>
                            <div className="col-md-1"><button className="btn btn-sm btn-link text-danger" onClick={() => removeItem(i)}>✕</button></div>
                        </div>
                    ))}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="primary" onClick={createLoad}>Create</Button>
                    <Button variant="secondary" onClick={() => setShow(false)}>Close</Button>
                </Modal.Footer>
            </Modal>

            {/* Reconcile */}
            <Modal show={!!recon} onHide={() => setRecon(null)} size="lg">
                <Modal.Header closeButton><Modal.Title>Reconcile load #{recon?.id}</Modal.Title></Modal.Header>
                <Modal.Body className="table-responsive">
                    <table className="table align-middle">
                        <thead><tr><th>Product</th><th>Loaded</th><th>Delivered</th><th>Returned</th></tr></thead>
                        <tbody>
                            {reconItems.map((it, i) => (
                                <tr key={it.id}>
                                    <td>{prodName(it.product_id)}</td><td>{it.loaded_qty}</td>
                                    <td><input type="number" className="form-control form-control-sm" style={{ width: 110 }} value={it.delivered_qty} onChange={setRecField(i, "delivered_qty")} /></td>
                                    <td><input type="number" className="form-control form-control-sm" style={{ width: 110 }} value={it.returned_qty} onChange={setRecField(i, "returned_qty")} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="primary" onClick={submitRecon}>Save reconciliation</Button>
                    <Button variant="secondary" onClick={() => setRecon(null)}>Cancel</Button>
                </Modal.Footer>
            </Modal>
        </MasterLayout>
    );
};

export default Distribution;
