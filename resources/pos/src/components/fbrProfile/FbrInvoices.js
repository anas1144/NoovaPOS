import React, { useEffect, useState } from "react";
import { Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import apiConfig from "../../config/apiConfig";
import moment from "moment";

const STATUS_OPTIONS = [
    { value: "", label: "All" },
    { value: "queued", label: "Queued" },
    { value: "sending", label: "Sending" },
    { value: "synced", label: "Synced" },
    { value: "failed", label: "Failed" },
    { value: "retrying", label: "Retrying" },
];

const statusBadge = (s) => {
    const m = {
        queued: "bg-light-warning",
        sending: "bg-light-info",
        synced: "bg-light-success",
        failed: "bg-light-danger",
        retrying: "bg-light-primary",
    };
    return `badge text-capitalize ${m[s] || "bg-light-secondary"}`;
};

const FbrInvoices = () => {
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(false);
    const [status, setStatus] = useState("");

    const load = async () => {
        setLoading(true);
        try {
            const res = await apiConfig.get("fbr-invoices", {
                params: { status: status || undefined },
            });
            setItems(res.data?.data?.data || res.data?.data || []);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, [status]);

    const submit = async (id) => {
        await apiConfig.post(`fbr-invoices/${id}/submit`);
        load();
    };

    const retry = async (id) => {
        await apiConfig.post(`fbr-invoices/${id}/retry`);
        load();
    };

    const processQueue = async () => {
        await apiConfig.post("fbr-invoices/process-queue");
        load();
    };

    const columns = [
        {
            name: "When",
            cell: (r) =>
                r.created_at ? moment(r.created_at).fromNow() : "—",
        },
        { name: "Invoice #", selector: (r) => r.invoice_no || `#${r.id}` },
        {
            name: "Business",
            selector: (r) => r.profile?.business_name || "—",
        },
        {
            name: "Mode",
            cell: (r) => (
                <span
                    className={`badge ${
                        r.mode === "production"
                            ? "bg-light-danger"
                            : "bg-light-warning"
                    } text-capitalize`}
                >
                    {r.mode}
                </span>
            ),
        },
        {
            name: "Status",
            cell: (r) => (
                <span className={statusBadge(r.status)}>{r.status}</span>
            ),
        },
        {
            name: "FBR Invoice #",
            cell: (r) =>
                r.fbr_invoice_no ? (
                    <code className="small">{r.fbr_invoice_no}</code>
                ) : (
                    "—"
                ),
        },
        {
            name: "Total",
            selector: (r) => r.total_amount,
            cell: (r) => Number(r.total_amount).toLocaleString(),
        },
        {
            name: "Attempts",
            selector: (r) => r.attempt_count,
            cell: (r) => (
                <span className="badge bg-light-primary">
                    {r.attempt_count}
                </span>
            ),
        },
        {
            name: "Action",
            right: true,
            cell: (r) => (
                <div className="d-flex gap-1">
                    {r.status === "queued" && (
                        <button
                            className="btn btn-sm btn-outline-primary"
                            onClick={() => submit(r.id)}
                        >
                            Submit
                        </button>
                    )}
                    {r.status === "failed" && (
                        <button
                            className="btn btn-sm btn-outline-warning"
                            onClick={() => retry(r.id)}
                        >
                            Retry
                        </button>
                    )}
                    {r.status === "synced" && (
                        <span className="text-success small">
                            ✓ {r.synced_at
                                ? moment(r.synced_at).fromNow()
                                : ""}
                        </span>
                    )}
                </div>
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="FBR Invoice Queue" />
            <div className="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h4 className="mb-0">FBR Submission Queue</h4>
                <div className="d-flex gap-2">
                    <select
                        className="form-control"
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                    >
                        {STATUS_OPTIONS.map((o) => (
                            <option key={o.value} value={o.value}>
                                {o.label}
                            </option>
                        ))}
                    </select>
                    <Button variant="outline-primary" onClick={load}>
                        Refresh
                    </Button>
                    <Button onClick={processQueue}>Process queue</Button>
                </div>
            </div>
            <ReactDataTable
                columns={columns}
                items={items}
                isLoading={loading}
                pagination={false}
                isShowSearch
                onChange={load}
            />
        </MasterLayout>
    );
};

export default FbrInvoices;
