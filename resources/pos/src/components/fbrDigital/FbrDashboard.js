import React, { useEffect, useState } from "react";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faFileInvoice, faCircleCheck, faRotate, faTriangleExclamation, faFilePen, faMoneyBillWave } from "@fortawesome/free-solid-svg-icons";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL } from "../../constants";

const Card = ({ icon, label, value, tone = "primary" }) => (
    <div className="col-xl-2 col-md-4 col-sm-6 mb-3">
        <div className="card h-100"><div className="card-body py-3 text-center">
            <FontAwesomeIcon icon={icon} className={`text-${tone} mb-2`} style={{ fontSize: 26 }} />
            <div className="fw-bold fs-4">{value}</div>
            <div className="text-muted small text-capitalize">{label}</div>
        </div></div>
    </div>
);

const FbrDashboard = () => {
    const [data, setData] = useState(null);

    useEffect(() => {
        apiConfig.get(apiBaseURL.FBR_DI_DASHBOARD).then((r) => setData(r.data?.data || null)).catch(() => {});
    }, []);

    const c = data?.cards || {};
    const q = data?.quota || {};
    const errors = data?.recent_errors || [];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="FBR Digital Dashboard" />

            <div className="row">
                <Card icon={faFileInvoice} label="Total Invoices" value={c.total_invoices ?? 0} />
                <Card icon={faCircleCheck} label="Synced" value={c.synced ?? 0} tone="success" />
                <Card icon={faRotate} label="Pending Sync" value={c.pending_sync ?? 0} tone="warning" />
                <Card icon={faTriangleExclamation} label="Rejected" value={c.rejected ?? 0} tone="danger" />
                <Card icon={faFilePen} label="Draft" value={c.draft ?? 0} tone="secondary" />
                <Card icon={faMoneyBillWave} label="Total Tax" value={Number(c.total_tax ?? 0).toLocaleString()} tone="info" />
            </div>

            <div className="row">
                <div className="col-lg-6 mb-4">
                    <div className="card h-100">
                        <div className="card-header"><h6 className="mb-0">Plan consumption ({q.plan_type || "single"})</h6></div>
                        <div className="card-body">
                            <div className="d-flex justify-content-between mb-1">
                                <span className="text-muted">Monthly invoices</span>
                                <span className="fw-semibold">
                                    {q.monthly_usage ?? 0}{q.monthly_invoice_limit != null ? ` / ${q.monthly_invoice_limit}` : " / ∞"}
                                </span>
                            </div>
                            {q.monthly_invoice_limit != null && (
                                <div className="progress mb-3" style={{ height: 8 }}>
                                    <div className={`progress-bar ${q.consumption_percent >= 90 ? "bg-danger" : "bg-primary"}`}
                                        style={{ width: `${q.consumption_percent || 0}%` }} />
                                </div>
                            )}
                            <div className="d-flex justify-content-between">
                                <span className="text-muted">Remaining</span>
                                <span className="fw-semibold">{q.remaining == null ? "Unlimited" : q.remaining}</span>
                            </div>
                            <div className="d-flex justify-content-between">
                                <span className="text-muted">Businesses</span>
                                <span className="fw-semibold">
                                    {q.businesses_count ?? 0}{q.businesses_limit != null ? ` / ${q.businesses_limit}` : " / ∞"}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-lg-6 mb-4">
                    <div className="card h-100">
                        <div className="card-header"><h6 className="mb-0">Recent errors</h6></div>
                        <div className="card-body">
                            {errors.length === 0 ? (
                                <div className="text-muted text-center py-4">No recent errors.</div>
                            ) : (
                                <ul className="list-unstyled mb-0">
                                    {errors.map((e, i) => (
                                        <li key={i} className="d-flex justify-content-between border-bottom py-2">
                                            <span><span className="fw-semibold">{e.code}</span> · {e.message}</span>
                                            <span className="badge bg-light-danger">{e.total_occurrences}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </MasterLayout>
    );
};

export default FbrDashboard;
