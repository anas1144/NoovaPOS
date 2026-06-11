import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Button, Modal } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const badge = (s) => ({ pass: "bg-light-success", fail: "bg-light-danger" }[s] || "bg-light-secondary");

const FbrSandbox = () => {
    const dispatch = useDispatch();
    const [rows, setRows] = useState([]);
    const [businesses, setBusinesses] = useState([]);
    const [businessId, setBusinessId] = useState("");
    const [running, setRunning] = useState(null);
    const [detail, setDetail] = useState(null);

    const toast = (text, type) => dispatch(addToast({ text, type }));

    const load = () => apiConfig.get(apiBaseURL.FBR_DI_SANDBOX).then((r) => setRows(r.data?.data || [])).catch(() => {});
    useEffect(() => {
        load();
        apiConfig.get(apiBaseURL.FBR_DI_BUSINESSES).then((r) => setBusinesses(r.data?.data || [])).catch(() => {});
    }, []);

    const run = (row) => {
        setRunning(row.id);
        apiConfig.post(`${apiBaseURL.FBR_DI_SANDBOX}/${row.id}/run`, businessId ? { fbr_business_id: businessId } : {})
            .then(() => { toast(`Ran ${row.code}`); load(); })
            .catch(({ response }) => toast(response?.data?.message || "Run failed", toastType.ERROR))
            .finally(() => setRunning(null));
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="FBR Sandbox Testing" />
            <div className="card">
                <div className="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 className="mb-0">FBR DI Sandbox Scenarios (SN001–SN028)</h6>
                    <select className="form-control form-control-sm w-auto" value={businessId} onChange={(e) => setBusinessId(e.target.value)}>
                        <option value="">Auto-pick business token</option>
                        {businesses.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                    </select>
                </div>
                <div className="card-body table-responsive">
                    <table className="table align-middle">
                        <thead><tr><th>Code</th><th>Description</th><th>Status</th><th>Last result</th><th>Last run</th><th className="text-end">Action</th></tr></thead>
                        <tbody>
                            {rows.length === 0 ? (
                                <tr><td colSpan={6} className="text-center text-muted py-4">No scenarios seeded.</td></tr>
                            ) : rows.map((r) => (
                                <tr key={r.id}>
                                    <td className="fw-semibold">{r.code}</td>
                                    <td>{r.title}</td>
                                    <td><span className={`badge ${badge(r.status)} text-capitalize`}>{r.status}</span></td>
                                    <td className="small text-muted">{r.last_result || "—"}</td>
                                    <td className="small text-muted">{r.last_run_at ? new Date(r.last_run_at).toLocaleString() : "—"}</td>
                                    <td className="text-end text-nowrap">
                                        {r.response && <button className="btn btn-sm btn-link" onClick={() => setDetail(r)}>Response</button>}
                                        <Button size="sm" variant="outline-primary" disabled={running === r.id} onClick={() => run(r)}>
                                            {running === r.id ? "Running…" : "Test"}
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <Modal show={!!detail} onHide={() => setDetail(null)} size="lg">
                <Modal.Header closeButton><Modal.Title>{detail?.code} response</Modal.Title></Modal.Header>
                <Modal.Body>
                    <pre style={{ whiteSpace: "pre-wrap", fontSize: 12 }}>{detail?.response || ""}</pre>
                </Modal.Body>
            </Modal>
        </MasterLayout>
    );
};

export default FbrSandbox;
