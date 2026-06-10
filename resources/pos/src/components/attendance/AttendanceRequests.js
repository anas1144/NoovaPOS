import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const badge = (s) => ({ pending: "bg-light-warning", approved: "bg-light-success", rejected: "bg-light-danger" }[s] || "bg-light-secondary");

const AttendanceRequests = () => {
    const dispatch = useDispatch();
    const [rows, setRows] = useState([]);
    const [isAdmin, setIsAdmin] = useState(false);
    const [form, setForm] = useState({ type: "correction", date: new Date().toISOString().slice(0, 10), requested_check_in: "", requested_check_out: "", reason: "" });
    const [busy, setBusy] = useState(false);

    const toast = (text, type) => dispatch(addToast({ text, type }));

    const load = () => {
        apiConfig.get(apiBaseURL.ATTENDANCE_REQUESTS)
            .then((res) => setRows(res.data?.data?.data || res.data?.data || []))
            .catch(() => {});
    };
    useEffect(() => {
        load();
        apiConfig.get(apiBaseURL.ATTENDANCE_STATUS).then((r) => setIsAdmin(!!r.data?.data?.is_admin)).catch(() => {});
    }, []);

    const setF = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));

    const submit = () => {
        setBusy(true);
        const body = { ...form };
        Object.keys(body).forEach((k) => { if (body[k] === "") delete body[k]; });
        apiConfig.post(apiBaseURL.ATTENDANCE_REQUESTS, body)
            .then(() => { toast("Request submitted."); setForm((f) => ({ ...f, reason: "", requested_check_in: "", requested_check_out: "" })); load(); })
            .catch(({ response }) => toast(response?.data?.message || "Submit failed", toastType.ERROR))
            .finally(() => setBusy(false));
    };

    const review = (req, action) => {
        apiConfig.post(`${apiBaseURL.ATTENDANCE_REQUESTS}/${req.id}/${action}`, {})
            .then(() => { toast(`Request ${action}d`); load(); })
            .catch(({ response }) => toast(response?.data?.message || "Action failed", toastType.ERROR));
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Attendance Requests" />

            <div className="card mb-4">
                <div className="card-header"><h6 className="mb-0">Submit a request</h6></div>
                <div className="card-body">
                    <div className="row g-2">
                        <div className="col-md-3">
                            <label className="form-label">Type</label>
                            <select className="form-control" value={form.type} onChange={setF("type")}>
                                <option value="correction">Correction</option>
                                <option value="missing_checkout">Missing check-out</option>
                                <option value="leave">Leave</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div className="col-md-3">
                            <label className="form-label">Date</label>
                            <input type="date" className="form-control" value={form.date} onChange={setF("date")} />
                        </div>
                        <div className="col-md-3">
                            <label className="form-label">Check-in</label>
                            <input type="datetime-local" className="form-control" value={form.requested_check_in} onChange={setF("requested_check_in")} />
                        </div>
                        <div className="col-md-3">
                            <label className="form-label">Check-out</label>
                            <input type="datetime-local" className="form-control" value={form.requested_check_out} onChange={setF("requested_check_out")} />
                        </div>
                        <div className="col-12">
                            <label className="form-label">Reason</label>
                            <textarea className="form-control" rows={2} value={form.reason} onChange={setF("reason")} placeholder="Why this correction is needed" />
                        </div>
                    </div>
                    <button className="btn btn-primary mt-3" disabled={busy} onClick={submit}>{busy ? "Submitting…" : "Submit request"}</button>
                </div>
            </div>

            <div className="card">
                <div className="card-header"><h6 className="mb-0">{isAdmin ? "All requests" : "My requests"}</h6></div>
                <div className="card-body table-responsive">
                    <table className="table align-middle">
                        <thead><tr>
                            <th>Employee</th><th>Type</th><th>Date</th><th>Requested in/out</th><th>Reason</th><th>Status</th>{isAdmin && <th className="text-end">Review</th>}
                        </tr></thead>
                        <tbody>
                            {rows.length === 0 ? (
                                <tr><td colSpan={isAdmin ? 7 : 6} className="text-center text-muted py-4">No requests.</td></tr>
                            ) : rows.map((r) => (
                                <tr key={r.id}>
                                    <td>{r.employee ? `${r.employee.first_name || ""} ${r.employee.last_name || ""}`.trim() : r.employee_id}</td>
                                    <td className="text-capitalize">{(r.type || "").replace("_", " ")}</td>
                                    <td>{r.date ? new Date(r.date).toLocaleDateString() : "—"}</td>
                                    <td className="small text-muted">
                                        {r.requested_check_in ? new Date(r.requested_check_in).toLocaleTimeString() : "—"} /{" "}
                                        {r.requested_check_out ? new Date(r.requested_check_out).toLocaleTimeString() : "—"}
                                    </td>
                                    <td className="small">{r.reason || "—"}</td>
                                    <td><span className={`badge ${badge(r.status)} text-capitalize`}>{r.status}</span></td>
                                    {isAdmin && (
                                        <td className="text-end">
                                            {r.status === "pending" ? (
                                                <>
                                                    <button className="btn btn-sm btn-outline-success me-1" onClick={() => review(r, "approve")}>Approve</button>
                                                    <button className="btn btn-sm btn-outline-danger" onClick={() => review(r, "reject")}>Reject</button>
                                                </>
                                            ) : <span className="text-muted small">{r.review_note || "—"}</span>}
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </MasterLayout>
    );
};

export default AttendanceRequests;
