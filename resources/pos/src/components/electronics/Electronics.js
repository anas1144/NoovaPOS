import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const today = () => new Date().toISOString().slice(0, 10);

const Electronics = () => {
    const dispatch = useDispatch();
    const [tab, setTab] = useState("serials");
    const [serials, setSerials] = useState([]);
    const [warranties, setWarranties] = useState([]);
    const [products, setProducts] = useState([]);

    const [serialForm, setSerialForm] = useState({ product_id: "", serials: "" });
    const [warForm, setWarForm] = useState({ product_id: "", serial_no: "", start_date: today(), months: "12", note: "" });
    const [lookupSerial, setLookupSerial] = useState("");
    const [lookupResult, setLookupResult] = useState(null);

    const toast = (text, type) => dispatch(addToast({ text, type }));
    const arr = (r) => r.data?.data?.data || r.data?.data || [];

    const loadSerials = () => apiConfig.get(apiBaseURL.ELECTRONICS_SERIALS).then((r) => setSerials(arr(r))).catch(() => {});
    const loadWarranties = () => apiConfig.get(apiBaseURL.ELECTRONICS_WARRANTIES).then((r) => setWarranties(arr(r))).catch(() => {});
    const loadProducts = () => apiConfig.get(apiBaseURL.PRODUCTS_LIST, { params: { page_size: 0 } }).then((r) => setProducts(arr(r))).catch(() => {});
    useEffect(() => { loadSerials(); loadWarranties(); loadProducts(); }, []);

    const pName = (p) => `${p.name || p.attributes?.name || `#${p.id}`}`;

    const addSerials = () => {
        if (!serialForm.product_id || !serialForm.serials.trim()) { toast("Product + serials required", toastType.ERROR); return; }
        apiConfig.post(apiBaseURL.ELECTRONICS_SERIALS, serialForm)
            .then((r) => { toast(r.data?.message || "Added."); setSerialForm({ product_id: "", serials: "" }); loadSerials(); })
            .catch(({ response }) => toast(response?.data?.message || "Failed", toastType.ERROR));
    };
    const setStatus = (s, status) => apiConfig.patch(`${apiBaseURL.ELECTRONICS_SERIALS}/${s.id}`, { status })
        .then(() => { toast("Updated."); loadSerials(); }).catch(() => {});

    const register = () => {
        if (!warForm.product_id || !warForm.serial_no) { toast("Product + serial required", toastType.ERROR); return; }
        apiConfig.post(apiBaseURL.ELECTRONICS_WARRANTIES, warForm)
            .then(() => { toast("Warranty registered."); setWarForm((f) => ({ ...f, serial_no: "", note: "" })); loadWarranties(); })
            .catch(({ response }) => toast(response?.data?.message || "Failed", toastType.ERROR));
    };

    const doLookup = () => {
        apiConfig.get(apiBaseURL.ELECTRONICS_WARRANTY_LOOKUP, { params: { serial_no: lookupSerial } })
            .then((r) => setLookupResult(r.data?.data)).catch(() => setLookupResult({ found: false }));
    };

    const TABS = [{ key: "serials", label: "Serials" }, { key: "warranties", label: "Warranties" }, { key: "lookup", label: "Warranty Lookup" }];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Electronics" />

            <ul className="nav nav-pills mb-3">
                {TABS.map((t) => (
                    <li className="nav-item" key={t.key}>
                        <button className={`nav-link ${tab === t.key ? "active" : ""}`} onClick={() => setTab(t.key)}>{t.label}</button>
                    </li>
                ))}
            </ul>

            {tab === "serials" && (
                <>
                    <div className="card mb-3"><div className="card-body">
                        <div className="row g-2 align-items-end">
                            <div className="col-md-4"><label className="form-label">Product</label>
                                <select className="form-control" value={serialForm.product_id} onChange={(e) => setSerialForm((f) => ({ ...f, product_id: e.target.value }))}>
                                    <option value="">—</option>{products.map((p) => <option key={p.id} value={p.id}>{pName(p)}</option>)}
                                </select></div>
                            <div className="col-md-6"><label className="form-label">Serial numbers (one per line / comma)</label>
                                <textarea className="form-control" rows={2} value={serialForm.serials} onChange={(e) => setSerialForm((f) => ({ ...f, serials: e.target.value }))} /></div>
                            <div className="col-md-2"><Button variant="primary" className="w-100" onClick={addSerials}>Add</Button></div>
                        </div>
                    </div></div>
                    <div className="card"><div className="card-body table-responsive">
                        <table className="table align-middle">
                            <thead><tr><th>Serial</th><th>Product</th><th>Status</th><th>Sold</th><th className="text-end">Set status</th></tr></thead>
                            <tbody>
                                {serials.length === 0 ? <tr><td colSpan={5} className="text-center text-muted py-4">No serials.</td></tr>
                                    : serials.map((s) => (<tr key={s.id}><td className="fw-semibold">{s.serial_no}</td><td>{s.product?.name || s.product_id}</td>
                                        <td className="text-capitalize">{(s.status || "").replace("_", " ")}</td>
                                        <td className="small text-muted">{s.sold_at ? new Date(s.sold_at).toLocaleDateString() : "—"}</td>
                                        <td className="text-end">
                                            {["in_stock", "sold", "returned", "faulty"].map((st) => (
                                                <button key={st} className={`btn btn-sm btn-link ${s.status === st ? "fw-bold" : ""}`} onClick={() => setStatus(s, st)}>{st.replace("_", " ")}</button>
                                            ))}
                                        </td></tr>))}
                            </tbody>
                        </table>
                    </div></div>
                </>
            )}

            {tab === "warranties" && (
                <>
                    <div className="card mb-3"><div className="card-body">
                        <div className="row g-2 align-items-end">
                            <div className="col-md-3"><label className="form-label">Product</label>
                                <select className="form-control" value={warForm.product_id} onChange={(e) => setWarForm((f) => ({ ...f, product_id: e.target.value }))}>
                                    <option value="">—</option>{products.map((p) => <option key={p.id} value={p.id}>{pName(p)}</option>)}
                                </select></div>
                            <div className="col-md-3"><label className="form-label">Serial</label>
                                <input className="form-control" value={warForm.serial_no} onChange={(e) => setWarForm((f) => ({ ...f, serial_no: e.target.value }))} /></div>
                            <div className="col-md-2"><label className="form-label">Start</label>
                                <input type="date" className="form-control" value={warForm.start_date} onChange={(e) => setWarForm((f) => ({ ...f, start_date: e.target.value }))} /></div>
                            <div className="col-md-2"><label className="form-label">Months</label>
                                <input type="number" className="form-control" value={warForm.months} onChange={(e) => setWarForm((f) => ({ ...f, months: e.target.value }))} /></div>
                            <div className="col-md-2"><Button variant="primary" className="w-100" onClick={register}>Register</Button></div>
                        </div>
                    </div></div>
                    <div className="card"><div className="card-body table-responsive">
                        <table className="table align-middle">
                            <thead><tr><th>Serial</th><th>Product</th><th>Start</th><th>End</th><th>Months</th><th>Status</th></tr></thead>
                            <tbody>
                                {warranties.length === 0 ? <tr><td colSpan={6} className="text-center text-muted py-4">No warranties.</td></tr>
                                    : warranties.map((w) => (<tr key={w.id}><td className="fw-semibold">{w.serial_no}</td><td>{w.product?.name || w.product_id}</td>
                                        <td>{w.start_date ? new Date(w.start_date).toLocaleDateString() : "—"}</td>
                                        <td>{w.end_date ? new Date(w.end_date).toLocaleDateString() : "—"}</td><td>{w.months}</td>
                                        <td className="text-capitalize">{w.status}</td></tr>))}
                            </tbody>
                        </table>
                    </div></div>
                </>
            )}

            {tab === "lookup" && (
                <div className="card"><div className="card-body">
                    <div className="d-flex gap-2 mb-3" style={{ maxWidth: 420 }}>
                        <input className="form-control" placeholder="Enter serial number" value={lookupSerial}
                            onChange={(e) => setLookupSerial(e.target.value)} onKeyDown={(e) => e.key === "Enter" && doLookup()} />
                        <Button variant="primary" onClick={doLookup}>Check</Button>
                    </div>
                    {lookupResult && (lookupResult.found ? (
                        <div className={`alert ${lookupResult.active ? "alert-light-success" : "alert-light-danger"}`}>
                            <div><strong>{lookupResult.product}</strong> · {lookupResult.serial_no}</div>
                            <div>{lookupResult.active ? `Under warranty — ${lookupResult.days_left} day(s) left` : "Warranty expired"}</div>
                            <div className="small text-muted">{lookupResult.start_date} → {lookupResult.end_date} ({lookupResult.months} months)</div>
                        </div>
                    ) : (
                        <div className="alert alert-light-warning">No warranty found for this serial.</div>
                    ))}
                </div></div>
            )}
        </MasterLayout>
    );
};

export default Electronics;
