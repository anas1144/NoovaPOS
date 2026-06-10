import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Button, Modal } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

/**
 * No-code device connector manager. Register any external biometric scanner,
 * face terminal, RFID/NFC reader or cloud API by describing its endpoint, auth
 * and request/response mapping — no code change needed to add a device.
 */
const blank = {
    name: "", modality: "fingerprint", run_on: "server",
    base_url: "", verify_path: "", enroll_path: "", http_method: "POST",
    auth_type: "none", auth_header: "", auth_token: "",
    request_template: '{\n  "employee_query": "{{code}}"\n}',
    response_success_path: "", success_value: "", response_employee_path: "",
    timeout: 15, status: true, notes: "",
};

const DeviceConnectors = () => {
    const dispatch = useDispatch();
    const [rows, setRows] = useState([]);
    const [show, setShow] = useState(false);
    const [form, setForm] = useState(blank);
    const [editing, setEditing] = useState(null);
    const [saving, setSaving] = useState(false);
    const [testResult, setTestResult] = useState(null);

    const toast = (text, type) => dispatch(addToast({ text, type }));

    const load = () => {
        apiConfig.get(apiBaseURL.ATTENDANCE_DEVICE_CONNECTORS)
            .then((res) => setRows(res.data?.data || []))
            .catch(() => {});
    };
    useEffect(() => { load(); }, []);

    const open = (row) => {
        setTestResult(null);
        if (row) {
            setEditing(row.id);
            setForm({
                ...blank, ...row,
                request_template: JSON.stringify(row.request_template ?? {}, null, 2),
                auth_token: "", // never prefill secrets
            });
        } else {
            setEditing(null);
            setForm(blank);
        }
        setShow(true);
    };

    const setF = (k) => (e) => {
        const v = e.target.type === "checkbox" ? e.target.checked : e.target.value;
        setForm((f) => ({ ...f, [k]: v }));
    };

    const payload = () => {
        let template = {};
        try { template = JSON.parse(form.request_template || "{}"); }
        catch (e) { throw new Error("Request template must be valid JSON."); }
        const out = { ...form, request_template: template, timeout: Number(form.timeout) || 15 };
        if (!out.auth_token) delete out.auth_token; // keep existing secret on edit
        return out;
    };

    const save = () => {
        let body;
        try { body = payload(); } catch (e) { toast(e.message, toastType.ERROR); return; }
        setSaving(true);
        const req = editing
            ? apiConfig.patch(`${apiBaseURL.ATTENDANCE_DEVICE_CONNECTORS}/${editing}`, body)
            : apiConfig.post(apiBaseURL.ATTENDANCE_DEVICE_CONNECTORS, body);
        req.then(() => { toast("Connector saved."); setShow(false); load(); })
            .catch(({ response }) => toast(response?.data?.message || "Save failed", toastType.ERROR))
            .finally(() => setSaving(false));
    };

    const remove = (row) => {
        apiConfig.delete(`${apiBaseURL.ATTENDANCE_DEVICE_CONNECTORS}/${row.id}`)
            .then(() => { toast("Connector removed."); load(); })
            .catch(() => toast("Delete failed", toastType.ERROR));
    };

    const runTest = () => {
        if (!editing) { toast("Save the connector first, then test.", toastType.ERROR); return; }
        setTestResult(null);
        apiConfig.post(`${apiBaseURL.ATTENDANCE_DEVICE_CONNECTORS}/${editing}/test`, { vars: { code: "TEST", employee_id: 1 } })
            .then((res) => setTestResult(res.data?.data))
            .catch(({ response }) => toast(response?.data?.message || "Test failed", toastType.ERROR));
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Attendance Devices" />

            <div className="card">
                <div className="card-header d-flex justify-content-between align-items-center">
                    <h6 className="mb-0">Device Connectors (no-code)</h6>
                    <Button variant="primary" onClick={() => open(null)}>+ Add device</Button>
                </div>
                <div className="card-body">
                    <p className="text-muted">
                        Connect external fingerprint scanners, face terminals, or RFID/NFC readers
                        without code. <strong>Server</strong> connectors are called by the platform
                        (cloud APIs, networked terminals). <strong>Client</strong> connectors are
                        called by the kiosk browser (local USB-scanner agents on <code>localhost</code>).
                    </p>
                    <div className="table-responsive">
                        <table className="table align-middle">
                            <thead>
                                <tr><th>Name</th><th>Modality</th><th>Runs on</th><th>Endpoint</th><th>Status</th><th></th></tr>
                            </thead>
                            <tbody>
                                {rows.length === 0 ? (
                                    <tr><td colSpan={6} className="text-center text-muted py-4">No devices configured.</td></tr>
                                ) : rows.map((r) => (
                                    <tr key={r.id}>
                                        <td>{r.name}</td>
                                        <td className="text-capitalize">{r.modality}</td>
                                        <td className="text-capitalize">{r.run_on}</td>
                                        <td className="text-muted small">{r.base_url ? `${r.base_url}${r.verify_path || ""}` : "—"}</td>
                                        <td>{r.status ? <span className="badge bg-light-success">on</span> : <span className="badge bg-light-secondary">off</span>}</td>
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
            </div>

            <Modal show={show} onHide={() => setShow(false)} size="lg">
                <Modal.Header closeButton><Modal.Title>{editing ? "Edit" : "Add"} device connector</Modal.Title></Modal.Header>
                <Modal.Body>
                    <div className="row">
                        <div className="col-md-6 mb-3">
                            <label className="form-label">Name</label>
                            <input className="form-control" value={form.name} onChange={setF("name")} placeholder="e.g. Mantra MFS100 (front desk)" />
                        </div>
                        <div className="col-md-3 mb-3">
                            <label className="form-label">Modality</label>
                            <select className="form-control" value={form.modality} onChange={setF("modality")}>
                                {["fingerprint", "face", "card", "rfid", "nfc"].map((m) => <option key={m} value={m}>{m}</option>)}
                            </select>
                        </div>
                        <div className="col-md-3 mb-3">
                            <label className="form-label">Runs on</label>
                            <select className="form-control" value={form.run_on} onChange={setF("run_on")}>
                                <option value="server">Server (cloud / networked)</option>
                                <option value="client">Client (local USB agent)</option>
                            </select>
                        </div>
                        <div className="col-md-7 mb-3">
                            <label className="form-label">Base URL</label>
                            <input className="form-control" value={form.base_url} onChange={setF("base_url")} placeholder="https://device.local or http://127.0.0.1:8190" />
                        </div>
                        <div className="col-md-3 mb-3">
                            <label className="form-label">Verify path</label>
                            <input className="form-control" value={form.verify_path} onChange={setF("verify_path")} placeholder="/verify" />
                        </div>
                        <div className="col-md-2 mb-3">
                            <label className="form-label">Method</label>
                            <select className="form-control" value={form.http_method} onChange={setF("http_method")}>
                                {["POST", "GET", "PUT", "PATCH"].map((m) => <option key={m} value={m}>{m}</option>)}
                            </select>
                        </div>
                        <div className="col-md-4 mb-3">
                            <label className="form-label">Auth type</label>
                            <select className="form-control" value={form.auth_type} onChange={setF("auth_type")}>
                                {["none", "api_key", "bearer", "basic"].map((m) => <option key={m} value={m}>{m}</option>)}
                            </select>
                        </div>
                        <div className="col-md-4 mb-3">
                            <label className="form-label">Auth header</label>
                            <input className="form-control" value={form.auth_header} onChange={setF("auth_header")} placeholder="X-API-Key" />
                        </div>
                        <div className="col-md-4 mb-3">
                            <label className="form-label">Auth token {editing && <span className="text-muted small">(leave blank to keep)</span>}</label>
                            <input className="form-control" type="password" value={form.auth_token} onChange={setF("auth_token")} />
                        </div>
                        <div className="col-12 mb-3">
                            <label className="form-label">Request template (JSON, use {"{{code}}"} / {"{{employee_id}}"} / {"{{descriptor}}"})</label>
                            <textarea className="form-control" rows={4} style={{ fontFamily: "monospace" }} value={form.request_template} onChange={setF("request_template")} />
                        </div>
                        <div className="col-md-4 mb-3">
                            <label className="form-label">Success path</label>
                            <input className="form-control" value={form.response_success_path} onChange={setF("response_success_path")} placeholder="data.matched" />
                        </div>
                        <div className="col-md-4 mb-3">
                            <label className="form-label">Success value</label>
                            <input className="form-control" value={form.success_value} onChange={setF("success_value")} placeholder="true" />
                        </div>
                        <div className="col-md-4 mb-3">
                            <label className="form-label">Employee path</label>
                            <input className="form-control" value={form.response_employee_path} onChange={setF("response_employee_path")} placeholder="data.employee_code" />
                        </div>
                        <div className="col-md-3 mb-3">
                            <label className="form-label">Timeout (s)</label>
                            <input type="number" className="form-control" value={form.timeout} onChange={setF("timeout")} />
                        </div>
                        <div className="col-md-3 mb-3 d-flex align-items-end">
                            <div className="form-check form-switch">
                                <input className="form-check-input" type="checkbox" id="dc_status" checked={!!form.status} onChange={setF("status")} />
                                <label className="form-check-label" htmlFor="dc_status">Enabled</label>
                            </div>
                        </div>
                    </div>

                    {testResult && (
                        <div className={`alert ${testResult.matched ? "alert-light-success" : "alert-light-warning"}`}>
                            Test: {testResult.matched ? "matched" : "no match"}
                            {testResult.employee ? ` → employee ${testResult.employee}` : ""}
                            {testResult.error ? ` · ${testResult.error}` : ""}
                        </div>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    {editing && <Button variant="outline-secondary" onClick={runTest}>Test</Button>}
                    <Button variant="primary" disabled={saving} onClick={save}>{saving ? "Saving…" : "Save"}</Button>
                    <Button variant="secondary" onClick={() => setShow(false)}>Close</Button>
                </Modal.Footer>
            </Modal>
        </MasterLayout>
    );
};

export default DeviceConnectors;
