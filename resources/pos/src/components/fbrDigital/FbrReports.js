import React, { useEffect, useState } from "react";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL } from "../../constants";

const firstOfMonth = () => { const d = new Date(); return new Date(d.getFullYear(), d.getMonth(), 1).toISOString().slice(0, 10); };
const today = () => new Date().toISOString().slice(0, 10);

const Stat = ({ label, value, tone = "primary" }) => (
    <div className="col-md-3 col-6 mb-3"><div className="card h-100"><div className="card-body py-3">
        <div className="text-muted small text-capitalize">{label}</div>
        <div className={`fw-bold fs-5 text-${tone}`}>{value}</div>
    </div></div></div>
);

const TABS = [
    { key: "sales", label: "Sales" },
    { key: "tax", label: "Tax" },
    { key: "sync", label: "Sync" },
    { key: "rejected", label: "Rejected" },
];

const FbrReports = () => {
    const [from, setFrom] = useState(firstOfMonth());
    const [to, setTo] = useState(today());
    const [tab, setTab] = useState("sales");
    const [data, setData] = useState({});

    const url = {
        sales: apiBaseURL.FBR_DI_REPORT_SALES,
        tax: apiBaseURL.FBR_DI_REPORT_TAX,
        sync: apiBaseURL.FBR_DI_REPORT_SYNC,
        rejected: apiBaseURL.FBR_DI_REPORT_REJECTED,
    };

    const run = () => {
        apiConfig.get(url[tab], { params: { from, to } })
            .then((r) => setData((d) => ({ ...d, [tab]: r.data?.data })))
            .catch(() => {});
    };
    useEffect(() => { run(); }, [tab]); // eslint-disable-line

    const sales = data.sales || {};
    const tax = data.tax || {};
    const sync = data.sync || {};
    const rejected = data.rejected || [];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="FBR Reports" />

            <div className="card mb-3"><div className="card-body">
                <div className="row align-items-end g-2">
                    <div className="col-md-3"><label className="form-label">From</label>
                        <input type="date" className="form-control" value={from} onChange={(e) => setFrom(e.target.value)} /></div>
                    <div className="col-md-3"><label className="form-label">To</label>
                        <input type="date" className="form-control" value={to} onChange={(e) => setTo(e.target.value)} /></div>
                    <div className="col-md-2"><button className="btn btn-primary w-100" onClick={run}>Run</button></div>
                </div>
            </div></div>

            <ul className="nav nav-pills mb-3">
                {TABS.map((t) => (
                    <li className="nav-item" key={t.key}>
                        <button className={`nav-link ${tab === t.key ? "active" : ""}`} onClick={() => setTab(t.key)}>{t.label}</button>
                    </li>
                ))}
            </ul>

            {tab === "sales" && (
                <>
                    <div className="row">
                        <Stat label="Invoices" value={sales.totals?.count ?? 0} />
                        <Stat label="Value excl. tax" value={Number(sales.totals?.value_excl ?? 0).toLocaleString()} />
                        <Stat label="Sales tax" value={Number(sales.totals?.sales_tax ?? 0).toLocaleString()} tone="info" />
                        <Stat label="Total incl. tax" value={Number(sales.totals?.total ?? 0).toLocaleString()} tone="success" />
                    </div>
                    <div className="card"><div className="card-body table-responsive">
                        <table className="table align-middle">
                            <thead><tr><th>FBR No.</th><th>Business</th><th>Buyer</th><th>Date</th><th>Excl. tax</th><th>Sales tax</th><th>Total</th></tr></thead>
                            <tbody>
                                {(sales.rows || []).length === 0 ? <tr><td colSpan={7} className="text-center text-muted py-4">No data.</td></tr>
                                    : sales.rows.map((r) => (
                                        <tr key={r.id}><td>{r.fbr_no || "—"}</td><td>{r.business}</td><td>{r.buyer}</td><td>{r.date}</td>
                                            <td>{Number(r.value_excl).toLocaleString()}</td><td>{Number(r.sales_tax).toLocaleString()}</td><td>{Number(r.total).toLocaleString()}</td></tr>
                                    ))}
                            </tbody>
                        </table>
                    </div></div>
                </>
            )}

            {tab === "tax" && (
                <div className="row">
                    <Stat label="Sales tax" value={Number(tax.sales_tax ?? 0).toLocaleString()} tone="info" />
                    <Stat label="Further tax" value={Number(tax.further_tax ?? 0).toLocaleString()} tone="warning" />
                    <Stat label="Total tax" value={Number(tax.total_tax ?? 0).toLocaleString()} tone="success" />
                    <Stat label="Invoices" value={tax.invoices ?? 0} />
                </div>
            )}

            {tab === "sync" && (
                <div className="row">
                    <Stat label="Draft" value={sync.draft ?? 0} tone="secondary" />
                    <Stat label="Pending" value={sync.pending_sync ?? 0} tone="warning" />
                    <Stat label="Syncing" value={sync.syncing ?? 0} tone="info" />
                    <Stat label="Synced" value={sync.synced ?? 0} tone="success" />
                    <Stat label="Rejected" value={sync.rejected ?? 0} tone="danger" />
                    <Stat label="Cancelled" value={sync.cancelled ?? 0} tone="secondary" />
                </div>
            )}

            {tab === "rejected" && (
                <div className="card"><div className="card-body table-responsive">
                    <table className="table align-middle">
                        <thead><tr><th>Business</th><th>Buyer</th><th>Date</th><th>Total</th><th>Error</th></tr></thead>
                        <tbody>
                            {rejected.length === 0 ? <tr><td colSpan={5} className="text-center text-muted py-4">No rejected invoices.</td></tr>
                                : rejected.map((r) => (
                                    <tr key={r.id}><td>{r.business}</td><td>{r.buyer}</td><td>{r.date}</td>
                                        <td>{Number(r.total).toLocaleString()}</td><td className="small text-danger">{r.error || "—"}</td></tr>
                                ))}
                        </tbody>
                    </table>
                </div></div>
            )}
        </MasterLayout>
    );
};

export default FbrReports;
