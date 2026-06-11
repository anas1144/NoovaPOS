import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Button, Modal } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const blank = {
    name: "", ntn_cnic: "", strn: "", province: "", address: "",
    business_activity: "", pos_id: "", environment: "sandbox",
    sandbox_token: "", production_token: "", status: true,
};

const PROVINCES = ["Punjab", "Sindh", "Khyber Pakhtunkhwa", "Balochistan", "Islamabad", "Azad Jammu and Kashmir", "Gilgit-Baltistan"];

const FbrBusinesses = () => {
    const dispatch = useDispatch();
    const [rows, setRows] = useState([]);
    const [show, setShow] = useState(false);
    const [form, setForm] = useState(blank);
    const [editing, setEditing] = useState(null);
    const [saving, setSaving] = useState(false);

    const toast = (text, type) => dispatch(addToast({ text, type }));

    const load = () => apiConfig.get(apiBaseURL.FBR_DI_BUSINESSES).then((r) => setRows(r.data?.data || [])).catch(() => {});
    useEffect(() => { load(); }, []);

    const open = (row) => {
        setEditing(row?.id || null);
        setForm(row ? { ...blank, ...row, sandbox_token: "", production_token: "" } : blank);
        setShow(true);
    };
    const setF = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.type === "checkbox" ? e.target.checked : e.target.value }));

    const save = () => {
        setSaving(true);
        const req = editing
            ? apiConfig.patch(`${apiBaseURL.FBR_DI_BUSINESSES}/${editing}`, form)
            : apiConfig.post(apiBaseURL.FBR_DI_BUSINESSES, form);
        req.then(() => { toast("Business saved."); setShow(false); load(); })
            .catch(({ response }) => toast(response?.data?.message || "Save failed", toastType.ERROR))
            .finally(() => setSaving(false));
    };

    const remove = (row) => {
        apiConfig.delete(`${apiBaseURL.FBR_DI_BUSINESSES}/${row.id}`)
            .then(() => { toast("Business removed."); load(); })
            .catch(({ response }) => toast(response?.data?.message || "Delete failed", toastType.ERROR));
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="FBR Businesses" />
            <div className="card">
                <div className="card-header d-flex justify-content-between align-items-center">
                    <h6 className="mb-0">Businesses (FBR sellers)</h6>
                    <Button variant="primary" onClick={() => open(null)}>+ Add business</Button>
                </div>
                <div className="card-body table-responsive">
                    <table className="table align-middle">
                        <thead><tr><th>Name</th><th>NTN/CNIC</th><th>STRN</th><th>Province</th><th>Env</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            {rows.length === 0 ? (
                                <tr><td colSpan={7} className="text-center text-muted py-4">No businesses yet.</td></tr>
                            ) : rows.map((r) => (
                                <tr key={r.id}>
                                    <td>{r.name}</td><td>{r.ntn_cnic || "—"}</td><td>{r.strn || "—"}</td>
                                    <td>{r.province || "—"}</td><td className="text-capitalize">{r.environment}</td>
                                    <td>{r.status ? <span className="badge bg-light-success">active</span> : <span className="badge bg-light-secondary">off</span>}</td>
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

            <Modal show={show} onHide={() => setShow(false)} size="lg">
                <Modal.Header closeButton><Modal.Title>{editing ? "Edit" : "Add"} business</Modal.Title></Modal.Header>
                <Modal.Body>
                    <div className="row">
                        <div className="col-md-6 mb-3"><label className="form-label">Business name *</label>
                            <input className="form-control" value={form.name} onChange={setF("name")} /></div>
                        <div className="col-md-3 mb-3"><label className="form-label">NTN / CNIC</label>
                            <input className="form-control" value={form.ntn_cnic} onChange={setF("ntn_cnic")} /></div>
                        <div className="col-md-3 mb-3"><label className="form-label">STRN</label>
                            <input className="form-control" value={form.strn} onChange={setF("strn")} /></div>
                        <div className="col-md-4 mb-3"><label className="form-label">Province</label>
                            <select className="form-control" value={form.province} onChange={setF("province")}>
                                <option value="">—</option>{PROVINCES.map((p) => <option key={p} value={p}>{p}</option>)}
                            </select></div>
                        <div className="col-md-4 mb-3"><label className="form-label">Business activity</label>
                            <input className="form-control" value={form.business_activity} onChange={setF("business_activity")} /></div>
                        <div className="col-md-4 mb-3"><label className="form-label">POS ID</label>
                            <input className="form-control" value={form.pos_id} onChange={setF("pos_id")} /></div>
                        <div className="col-12 mb-3"><label className="form-label">Address</label>
                            <input className="form-control" value={form.address} onChange={setF("address")} /></div>
                        <div className="col-md-4 mb-3"><label className="form-label">Environment</label>
                            <select className="form-control" value={form.environment} onChange={setF("environment")}>
                                <option value="sandbox">Sandbox</option><option value="production">Production</option>
                            </select></div>
                        <div className="col-md-4 mb-3"><label className="form-label">Sandbox token {editing && <span className="text-muted small">(blank=keep)</span>}</label>
                            <input className="form-control" type="password" value={form.sandbox_token} onChange={setF("sandbox_token")} /></div>
                        <div className="col-md-4 mb-3"><label className="form-label">Production token {editing && <span className="text-muted small">(blank=keep)</span>}</label>
                            <input className="form-control" type="password" value={form.production_token} onChange={setF("production_token")} /></div>
                        <div className="col-12"><div className="form-check form-switch">
                            <input className="form-check-input" type="checkbox" id="b_status" checked={!!form.status} onChange={setF("status")} />
                            <label className="form-check-label" htmlFor="b_status">Active</label>
                        </div></div>
                    </div>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="primary" disabled={saving || !form.name} onClick={save}>{saving ? "Saving…" : "Save"}</Button>
                    <Button variant="secondary" onClick={() => setShow(false)}>Close</Button>
                </Modal.Footer>
            </Modal>
        </MasterLayout>
    );
};

export default FbrBusinesses;
