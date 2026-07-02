import React, { useEffect, useRef, useState } from "react";
import { useDispatch } from "react-redux";
import { Button, Modal } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const blank = {
    name: "", hs_code: "", uom: "", rate_per_unit: "", rate_of_sales_tax: "18",
    sro_no: "", sro_item_serial: "", sale_type: "", category: "", status: true,
    fbr_business_id: "",
};

const FbrProducts = () => {
    const dispatch = useDispatch();
    const [rows, setRows] = useState([]);
    const [businesses, setBusinesses] = useState([]);
    const [show, setShow] = useState(false);
    const [form, setForm] = useState(blank);
    const [editing, setEditing] = useState(null);
    const [saving, setSaving] = useState(false);
    const [search, setSearch] = useState("");

    // HS-code autocomplete state
    const [hsResults, setHsResults] = useState([]);
    const [hsOpen, setHsOpen] = useState(false);
    const hsTimer = useRef(null);

    const toast = (text, type) => dispatch(addToast({ text, type }));

    const load = () =>
        apiConfig
            .get(apiBaseURL.FBR_DI_PRODUCTS, { params: { search } })
            .then((r) => setRows(r.data?.data || []))
            .catch(() => {});

    useEffect(() => { load(); /* eslint-disable-next-line */ }, [search]);
    useEffect(() => {
        apiConfig.get(apiBaseURL.FBR_DI_BUSINESSES).then((r) => setBusinesses(r.data?.data || [])).catch(() => {});
    }, []);

    const open = (row) => {
        setEditing(row?.id || null);
        setForm(row ? { ...blank, ...row } : blank);
        setHsResults([]);
        setHsOpen(false);
        setShow(true);
    };

    const setF = (k) => (e) =>
        setForm((f) => ({ ...f, [k]: e.target.type === "checkbox" ? e.target.checked : e.target.value }));

    // Debounced FBR HS-code lookup.
    const onHsInput = (e) => {
        const v = e.target.value;
        setForm((f) => ({ ...f, hs_code: v }));
        if (hsTimer.current) clearTimeout(hsTimer.current);
        if (!v || v.length < 2) { setHsResults([]); setHsOpen(false); return; }
        hsTimer.current = setTimeout(() => {
            apiConfig
                .get(apiBaseURL.FBR_DI_HS_CODES, { params: { q: v, fbr_business_id: form.fbr_business_id || undefined } })
                .then((r) => { setHsResults(r.data?.data || []); setHsOpen(true); })
                .catch(() => { setHsResults([]); setHsOpen(false); });
        }, 350);
    };

    const pickHs = (hit) => {
        setForm((f) => ({ ...f, hs_code: hit.hs_code, name: f.name || hit.description }));
        setHsOpen(false);
    };

    const save = () => {
        if (!form.name.trim()) { toast("Product name is required.", toastType.ERROR); return; }
        setSaving(true);
        const req = editing
            ? apiConfig.patch(`${apiBaseURL.FBR_DI_PRODUCTS}/${editing}`, form)
            : apiConfig.post(apiBaseURL.FBR_DI_PRODUCTS, form);
        req
            .then(() => { toast(`Product ${editing ? "updated" : "created"}.`); setShow(false); load(); })
            .catch(({ response }) => toast(response?.data?.message || "Save failed", toastType.ERROR))
            .finally(() => setSaving(false));
    };

    const remove = (row) => {
        if (!window.confirm(`Delete "${row.name}"?`)) return;
        apiConfig
            .delete(`${apiBaseURL.FBR_DI_PRODUCTS}/${row.id}`)
            .then(() => { toast("Product removed."); load(); })
            .catch(({ response }) => toast(response?.data?.message || "Delete failed", toastType.ERROR));
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="FBR DI Products" />
            <div className="card">
                <div className="card-header d-flex justify-content-between align-items-center">
                    <h5 className="mb-0">FBR Digital Invoice — Products</h5>
                    <div className="d-flex gap-2">
                        <input
                            className="form-control form-control-sm"
                            placeholder="Search name / HS code"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            style={{ width: 220 }}
                        />
                        <Button variant="primary" size="sm" onClick={() => open(null)}>+ Add Product</Button>
                    </div>
                </div>
                <div className="card-body table-responsive">
                    <table className="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>HS Code</th>
                                <th>UoM</th>
                                <th className="text-end">Rate</th>
                                <th className="text-end">Tax %</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th className="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.length === 0 && (
                                <tr><td colSpan={8} className="text-center text-muted py-4">No products yet.</td></tr>
                            )}
                            {rows.map((r) => (
                                <tr key={r.id}>
                                    <td>{r.name}</td>
                                    <td>{r.hs_code || "-"}</td>
                                    <td>{r.uom || "-"}</td>
                                    <td className="text-end">{Number(r.rate_per_unit).toFixed(2)}</td>
                                    <td className="text-end">{Number(r.rate_of_sales_tax).toFixed(2)}</td>
                                    <td>{r.category || "-"}</td>
                                    <td>
                                        <span className={`badge ${r.status ? "bg-light-success text-success" : "bg-light-danger text-danger"}`}>
                                            {r.status ? "active" : "inactive"}
                                        </span>
                                    </td>
                                    <td className="text-end">
                                        <button className="btn btn-sm btn-outline-primary me-1" onClick={() => open(r)}>Edit</button>
                                        <button className="btn btn-sm btn-outline-danger" onClick={() => remove(r)}>Delete</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <Modal show={show} onHide={() => setShow(false)} size="lg">
                <Modal.Header closeButton>
                    <Modal.Title>{editing ? "Edit" : "Add"} FBR DI Product</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <div className="row">
                        <div className="col-md-6 mb-3">
                            <label className="form-label">Business (optional)</label>
                            <select className="form-control" value={form.fbr_business_id || ""} onChange={setF("fbr_business_id")}>
                                <option value="">— All / unassigned —</option>
                                {businesses.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                            </select>
                        </div>
                        <div className="col-md-6 mb-3">
                            <label className="form-label">Product / Item Name *</label>
                            <input className="form-control" value={form.name} onChange={setF("name")} />
                        </div>
                        <div className="col-md-6 mb-3 position-relative">
                            <label className="form-label">HS Code</label>
                            <input
                                className="form-control"
                                value={form.hs_code}
                                onChange={onHsInput}
                                onFocus={() => hsResults.length && setHsOpen(true)}
                                autoComplete="off"
                                placeholder="Type to search FBR HS codes"
                            />
                            {hsOpen && hsResults.length > 0 && (
                                <div className="border rounded bg-white shadow-sm position-absolute w-100" style={{ zIndex: 20, maxHeight: 240, overflowY: "auto" }}>
                                    {hsResults.map((hit, i) => (
                                        <button
                                            type="button"
                                            key={i}
                                            className="dropdown-item text-wrap py-2"
                                            onClick={() => pickHs(hit)}
                                        >
                                            <span className="fw-semibold">{hit.hs_code}</span>
                                            {hit.description ? <span className="text-muted"> — {hit.description}</span> : null}
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>
                        <div className="col-md-3 mb-3">
                            <label className="form-label">UoM</label>
                            <input className="form-control" value={form.uom} onChange={setF("uom")} placeholder="e.g. PCS, KG" />
                        </div>
                        <div className="col-md-3 mb-3">
                            <label className="form-label">Category</label>
                            <input className="form-control" value={form.category} onChange={setF("category")} />
                        </div>
                        <div className="col-md-3 mb-3">
                            <label className="form-label">Rate / Unit</label>
                            <input type="number" min="0" step="0.01" className="form-control" value={form.rate_per_unit} onChange={setF("rate_per_unit")} />
                        </div>
                        <div className="col-md-3 mb-3">
                            <label className="form-label">Sales Tax %</label>
                            <input type="number" min="0" max="100" step="0.01" className="form-control" value={form.rate_of_sales_tax} onChange={setF("rate_of_sales_tax")} />
                        </div>
                        <div className="col-md-3 mb-3">
                            <label className="form-label">Sale Type</label>
                            <input className="form-control" value={form.sale_type} onChange={setF("sale_type")} />
                        </div>
                        <div className="col-md-3 mb-3">
                            <label className="form-label">SRO No.</label>
                            <input className="form-control" value={form.sro_no} onChange={setF("sro_no")} />
                        </div>
                        <div className="col-md-3 mb-3">
                            <label className="form-label">SRO Item Serial</label>
                            <input className="form-control" value={form.sro_item_serial} onChange={setF("sro_item_serial")} />
                        </div>
                        <div className="col-md-3 mb-3 d-flex align-items-end">
                            <label className="form-check">
                                <input type="checkbox" className="form-check-input me-2" checked={!!form.status} onChange={setF("status")} />
                                Active
                            </label>
                        </div>
                    </div>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="primary" onClick={save} disabled={saving}>{saving ? "Saving…" : "Save"}</Button>
                    <Button variant="secondary" onClick={() => setShow(false)}>Cancel</Button>
                </Modal.Footer>
            </Modal>
        </MasterLayout>
    );
};

export default FbrProducts;
