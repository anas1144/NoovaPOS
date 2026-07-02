import React, { useEffect, useMemo, useState } from "react";
import { useDispatch } from "react-redux";
import { useNavigate, useParams, useLocation } from "react-router-dom";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const PROVINCES = ["Punjab", "Sindh", "Khyber Pakhtunkhwa", "Balochistan", "Islamabad", "Azad Jammu and Kashmir", "Gilgit-Baltistan"];
const emptyItem = () => ({ description: "", hs_code: "", uom: "", rate_per_unit: "", quantity: "1", rate_of_sales_tax: "18" });
const today = () => new Date().toISOString().slice(0, 10);

const FbrInvoiceForm = () => {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const { id } = useParams();
    const location = useLocation();
    const isEdit = location.pathname.endsWith("/edit");
    const readOnly = !!id && !isEdit; // /invoices/:id (view)

    const [businesses, setBusinesses] = useState([]);
    const [products, setProducts] = useState([]);
    const [saving, setSaving] = useState(false);
    const [head, setHead] = useState({
        fbr_business_id: "", invoice_date: today(), invoice_type: "sale", ref_inv_no: "",
        buyer_name: "", buyer_ntn_cnic: "", buyer_registration_type: "unregistered",
        buyer_province: "", buyer_address: "", further_tax: "0",
    });
    const [items, setItems] = useState([emptyItem()]);
    const [meta, setMeta] = useState({ status: "draft", fbr_invoice_no: null });

    const toast = (text, type) => dispatch(addToast({ text, type }));

    useEffect(() => {
        apiConfig.get(apiBaseURL.FBR_DI_BUSINESSES).then((r) => setBusinesses(r.data?.data || [])).catch(() => {});
        apiConfig.get(apiBaseURL.FBR_DI_PRODUCTS, { params: { status: 1 } }).then((r) => setProducts(r.data?.data || [])).catch(() => {});
    }, []);

    // Selecting a saved product auto-fills the line (free text still editable).
    const fillFromProduct = (i, productId) => {
        const p = products.find((x) => String(x.id) === String(productId));
        if (!p) return;
        setItems((rows) => rows.map((r, idx) => idx === i ? {
            ...r,
            description: p.name || r.description,
            hs_code: p.hs_code || r.hs_code,
            uom: p.uom || r.uom,
            rate_per_unit: p.rate_per_unit != null ? String(p.rate_per_unit) : r.rate_per_unit,
            rate_of_sales_tax: p.rate_of_sales_tax != null ? String(p.rate_of_sales_tax) : r.rate_of_sales_tax,
        } : r));
    };

    useEffect(() => {
        if (!id) return;
        apiConfig.get(`${apiBaseURL.FBR_DI_INVOICES}/${id}`).then((r) => {
            const inv = r.data?.data;
            if (!inv) return;
            setHead({
                fbr_business_id: inv.fbr_business_id, invoice_date: (inv.invoice_date || "").slice(0, 10),
                invoice_type: inv.invoice_type, ref_inv_no: inv.ref_inv_no || "",
                buyer_name: inv.buyer_name || "", buyer_ntn_cnic: inv.buyer_ntn_cnic || "",
                buyer_registration_type: inv.buyer_registration_type || "unregistered",
                buyer_province: inv.buyer_province || "", buyer_address: inv.buyer_address || "",
                further_tax: String(inv.further_tax ?? "0"),
            });
            setItems((inv.items || []).map((it) => ({
                description: it.description || "", hs_code: it.hs_code || "", uom: it.uom || "",
                rate_per_unit: String(it.rate_per_unit), quantity: String(it.quantity),
                rate_of_sales_tax: String(it.rate_of_sales_tax),
            })) || [emptyItem()]);
            setMeta({ status: inv.status, fbr_invoice_no: inv.fbr_invoice_no });
        }).catch(() => {});
    }, [id]);

    const setH = (k) => (e) => setHead((h) => ({ ...h, [k]: e.target.value }));
    const setItem = (i, k) => (e) => setItems((rows) => rows.map((r, idx) => idx === i ? { ...r, [k]: e.target.value } : r));
    const addRow = () => setItems((r) => [...r, emptyItem()]);
    const removeRow = (i) => setItems((r) => (r.length > 1 ? r.filter((_, idx) => idx !== i) : r));

    const lineCalc = (it) => {
        const excl = (Number(it.rate_per_unit) || 0) * (Number(it.quantity) || 0);
        const tax = excl * (Number(it.rate_of_sales_tax) || 0) / 100;
        return { excl, tax, incl: excl + tax };
    };
    const totals = useMemo(() => {
        let excl = 0, tax = 0;
        items.forEach((it) => { const c = lineCalc(it); excl += c.excl; tax += c.tax; });
        const further = Number(head.further_tax) || 0;
        return { excl, tax, further, total: excl + tax + further };
    }, [items, head.further_tax]);

    const save = () => {
        if (!head.fbr_business_id) { toast("Select a business", toastType.ERROR); return; }
        if (!items.some((it) => it.description.trim())) { toast("Add at least one item", toastType.ERROR); return; }
        setSaving(true);
        const body = { ...head, items: items.filter((it) => it.description.trim()) };
        const req = id && isEdit
            ? apiConfig.patch(`${apiBaseURL.FBR_DI_INVOICES}/${id}`, body)
            : apiConfig.post(apiBaseURL.FBR_DI_INVOICES, body);
        req.then(() => { toast("Invoice saved as draft."); navigate("/app/fbr-di/invoices"); })
            .catch(({ response }) => toast(response?.data?.message || "Save failed", toastType.ERROR))
            .finally(() => setSaving(false));
    };

    const title = readOnly ? "View FBR Invoice" : (id ? "Edit FBR Invoice" : "New FBR Invoice");

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={title} />

            <div className="d-flex justify-content-between align-items-center mb-3">
                <h5 className="mb-0">{title}{meta.fbr_invoice_no ? ` · FBR #${meta.fbr_invoice_no}` : ""}</h5>
                <div>
                    {readOnly && <button className="btn btn-outline-secondary me-2" onClick={() => window.print()}>Print</button>}
                    <button className="btn btn-light" onClick={() => navigate("/app/fbr-di/invoices")}>Back</button>
                </div>
            </div>

            <div className="card mb-3"><div className="card-body row">
                <div className="col-md-4 mb-3"><label className="form-label">Business (seller) *</label>
                    <select className="form-control" value={head.fbr_business_id} onChange={setH("fbr_business_id")} disabled={readOnly}>
                        <option value="">— Select —</option>
                        {businesses.map((b) => <option key={b.id} value={b.id}>{b.name}{b.ntn_cnic ? ` (${b.ntn_cnic})` : ""}</option>)}
                    </select></div>
                <div className="col-md-3 mb-3"><label className="form-label">Invoice date *</label>
                    <input type="date" className="form-control" value={head.invoice_date} onChange={setH("invoice_date")} disabled={readOnly} /></div>
                <div className="col-md-2 mb-3"><label className="form-label">Type</label>
                    <select className="form-control" value={head.invoice_type} onChange={setH("invoice_type")} disabled={readOnly}>
                        <option value="sale">Sale</option><option value="credit_note">Credit note</option><option value="debit_note">Debit note</option>
                    </select></div>
                <div className="col-md-3 mb-3"><label className="form-label">Ref invoice no.</label>
                    <input className="form-control" value={head.ref_inv_no} onChange={setH("ref_inv_no")} disabled={readOnly} /></div>
            </div></div>

            <div className="card mb-3"><div className="card-header"><h6 className="mb-0">Buyer</h6></div><div className="card-body row">
                <div className="col-md-4 mb-3"><label className="form-label">Buyer name</label>
                    <input className="form-control" value={head.buyer_name} onChange={setH("buyer_name")} disabled={readOnly} /></div>
                <div className="col-md-3 mb-3"><label className="form-label">NTN / CNIC</label>
                    <input className="form-control" value={head.buyer_ntn_cnic} onChange={setH("buyer_ntn_cnic")} disabled={readOnly} /></div>
                <div className="col-md-2 mb-3"><label className="form-label">Registration</label>
                    <select className="form-control" value={head.buyer_registration_type} onChange={setH("buyer_registration_type")} disabled={readOnly}>
                        <option value="unregistered">Unregistered</option><option value="registered">Registered</option>
                    </select></div>
                <div className="col-md-3 mb-3"><label className="form-label">Province</label>
                    <select className="form-control" value={head.buyer_province} onChange={setH("buyer_province")} disabled={readOnly}>
                        <option value="">—</option>{PROVINCES.map((p) => <option key={p} value={p}>{p}</option>)}
                    </select></div>
                <div className="col-12 mb-1"><label className="form-label">Address</label>
                    <input className="form-control" value={head.buyer_address} onChange={setH("buyer_address")} disabled={readOnly} /></div>
            </div></div>

            <div className="card mb-3">
                <div className="card-header d-flex justify-content-between align-items-center">
                    <h6 className="mb-0">Items</h6>
                    {!readOnly && <button className="btn btn-sm btn-outline-primary" onClick={addRow}>+ Add item</button>}
                </div>
                <div className="card-body table-responsive">
                    <table className="table align-middle">
                        <thead><tr>
                            {!readOnly && <th>Product</th>}<th>HS code</th><th>Description</th><th>UoM</th><th>Rate</th><th>Qty</th><th>Excl. tax</th><th>Tax %</th><th>Sales tax</th><th>Incl. tax</th>{!readOnly && <th></th>}
                        </tr></thead>
                        <tbody>
                            {items.map((it, i) => { const c = lineCalc(it); return (
                                <tr key={i}>
                                    {!readOnly && (
                                        <td>
                                            <select className="form-control form-control-sm" style={{ width: 150 }} value="" onChange={(e) => fillFromProduct(i, e.target.value)}>
                                                <option value="">— pick —</option>
                                                {products.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                                            </select>
                                        </td>
                                    )}
                                    <td><input className="form-control form-control-sm" style={{ width: 110 }} value={it.hs_code} onChange={setItem(i, "hs_code")} disabled={readOnly} /></td>
                                    <td><input className="form-control form-control-sm" value={it.description} onChange={setItem(i, "description")} disabled={readOnly} /></td>
                                    <td><input className="form-control form-control-sm" style={{ width: 80 }} value={it.uom} onChange={setItem(i, "uom")} disabled={readOnly} /></td>
                                    <td><input type="number" className="form-control form-control-sm" style={{ width: 100 }} value={it.rate_per_unit} onChange={setItem(i, "rate_per_unit")} disabled={readOnly} /></td>
                                    <td><input type="number" className="form-control form-control-sm" style={{ width: 80 }} value={it.quantity} onChange={setItem(i, "quantity")} disabled={readOnly} /></td>
                                    <td className="text-end">{c.excl.toFixed(2)}</td>
                                    <td><input type="number" className="form-control form-control-sm" style={{ width: 70 }} value={it.rate_of_sales_tax} onChange={setItem(i, "rate_of_sales_tax")} disabled={readOnly} /></td>
                                    <td className="text-end">{c.tax.toFixed(2)}</td>
                                    <td className="text-end">{c.incl.toFixed(2)}</td>
                                    {!readOnly && <td><button className="btn btn-sm btn-link text-danger" onClick={() => removeRow(i)}>✕</button></td>}
                                </tr>
                            ); })}
                        </tbody>
                    </table>
                    <div className="row justify-content-end">
                        <div className="col-md-4">
                            <div className="d-flex justify-content-between"><span className="text-muted">Value excl. tax</span><span>{totals.excl.toFixed(2)}</span></div>
                            <div className="d-flex justify-content-between"><span className="text-muted">Sales tax</span><span>{totals.tax.toFixed(2)}</span></div>
                            <div className="d-flex justify-content-between align-items-center">
                                <span className="text-muted">Further tax</span>
                                <input type="number" className="form-control form-control-sm w-auto" value={head.further_tax} onChange={setH("further_tax")} disabled={readOnly} />
                            </div>
                            <hr className="my-1" />
                            <div className="d-flex justify-content-between fw-bold"><span>Total incl. tax</span><span>{totals.total.toFixed(2)}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            {!readOnly && (
                <button className="btn btn-primary" disabled={saving} onClick={save}>{saving ? "Saving…" : "Save draft"}</button>
            )}
        </MasterLayout>
    );
};

export default FbrInvoiceForm;
