import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Link, useNavigate } from "react-router-dom";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const STATUS_BADGE = {
    draft: "bg-light-secondary",
    pending_sync: "bg-light-warning",
    syncing: "bg-light-info",
    synced: "bg-light-primary",
    accepted: "bg-light-success",
    rejected: "bg-light-danger",
    cancelled: "bg-light-dark",
};

const FbrInvoices = () => {
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const [rows, setRows] = useState([]);
    const [status, setStatus] = useState("");
    const [search, setSearch] = useState("");

    const toast = (text, type) => dispatch(addToast({ text, type }));

    const load = () => {
        const params = { ...(status ? { status } : {}), ...(search ? { search } : {}) };
        apiConfig.get(apiBaseURL.FBR_DI_INVOICES, { params })
            .then((r) => setRows(r.data?.data?.data || r.data?.data || []))
            .catch(() => {});
    };
    useEffect(() => { load(); }, [status]); // eslint-disable-line

    const remove = (row) => {
        if (row.status !== "draft") { toast("Only a Draft invoice can be deleted.", toastType.ERROR); return; }
        apiConfig.delete(`${apiBaseURL.FBR_DI_INVOICES}/${row.id}`)
            .then(() => { toast("Draft deleted."); load(); })
            .catch(({ response }) => toast(response?.data?.message || "Delete failed", toastType.ERROR));
    };

    const sync = (row) => {
        apiConfig.post(`${apiBaseURL.FBR_DI_INVOICES}/${row.id}/sync`, {})
            .then(() => { toast("Queued for FBR sync."); load(); })
            .catch(({ response }) => toast(response?.data?.message || "Sync failed", toastType.ERROR));
    };
    const canSync = (s) => s === "draft" || s === "rejected";

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="FBR Invoices" />
            <div className="card">
                <div className="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 className="mb-0">Digital Invoices</h6>
                    <div className="d-flex gap-2">
                        <input className="form-control form-control-sm" placeholder="Search…" value={search}
                            onChange={(e) => setSearch(e.target.value)} onKeyDown={(e) => e.key === "Enter" && load()} />
                        <select className="form-control form-control-sm" value={status} onChange={(e) => setStatus(e.target.value)}>
                            <option value="">All statuses</option>
                            {Object.keys(STATUS_BADGE).map((s) => <option key={s} value={s}>{s.replace("_", " ")}</option>)}
                        </select>
                        <Link to="/app/fbr-di/invoices/create" className="btn btn-primary btn-sm text-nowrap">+ New invoice</Link>
                    </div>
                </div>
                <div className="card-body table-responsive">
                    <table className="table align-middle">
                        <thead><tr>
                            <th>#</th><th>Business</th><th>Buyer</th><th>Date</th><th>Total</th><th>FBR No.</th><th>Status</th><th className="text-end">Actions</th>
                        </tr></thead>
                        <tbody>
                            {rows.length === 0 ? (
                                <tr><td colSpan={8} className="text-center text-muted py-4">No invoices.</td></tr>
                            ) : rows.map((r) => (
                                <tr key={r.id}>
                                    <td>{r.local_no || r.id}</td>
                                    <td>{r.business?.name || "—"}</td>
                                    <td>{r.buyer_name || "—"}</td>
                                    <td>{r.invoice_date ? new Date(r.invoice_date).toLocaleDateString() : "—"}</td>
                                    <td>{Number(r.total_incl_tax).toLocaleString()}</td>
                                    <td>{r.fbr_invoice_no || "—"}</td>
                                    <td><span className={`badge ${STATUS_BADGE[r.status] || "bg-light-secondary"} text-capitalize`}>{(r.status || "").replace("_", " ")}</span></td>
                                    <td className="text-end text-nowrap">
                                        <button className="btn btn-sm btn-link" onClick={() => navigate(`/app/fbr-di/invoices/${r.id}`)}>View</button>
                                        {canSync(r.status) && (
                                            <button className="btn btn-sm btn-link text-success" onClick={() => sync(r)}>Sync</button>
                                        )}
                                        {r.status === "draft" && (
                                            <button className="btn btn-sm btn-link" onClick={() => navigate(`/app/fbr-di/invoices/${r.id}/edit`)}>Edit</button>
                                        )}
                                        {r.status === "draft" && (
                                            <button className="btn btn-sm btn-link text-danger" onClick={() => remove(r)}>Delete</button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </MasterLayout>
    );
};

export default FbrInvoices;
