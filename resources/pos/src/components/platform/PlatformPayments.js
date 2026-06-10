import React, { useEffect, useMemo, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import {
    fetchPlatformPayments,
    confirmPayment,
    rejectPayment,
} from "../../store/action/platformAction";

const fmtDate = (d) => (d ? new Date(d).toLocaleDateString() : "—");

const statusBadge = (s) => {
    const map = {
        paid: "bg-light-success",
        pending: "bg-light-warning",
        rejected: "bg-light-danger",
    };
    return (
        <span className={`badge ${map[s] || "bg-light-secondary"} text-capitalize`}>
            {s}
        </span>
    );
};

const PlatformPayments = () => {
    const dispatch = useDispatch();
    const payments = useSelector((s) => s.platform?.payments || []);
    const isLoading = useSelector((s) => s.isLoading);
    const [statusFilter, setStatusFilter] = useState("");

    useEffect(() => {
        dispatch(fetchPlatformPayments());
    }, []);

    const filtered = useMemo(
        () =>
            statusFilter
                ? payments.filter((p) => p.status === statusFilter)
                : payments,
        [payments, statusFilter]
    );

    const onReject = (id) => {
        const note = window.prompt("Reason for rejecting this payment? (optional)") || "";
        dispatch(rejectPayment(id, note));
    };

    const columns = [
        { name: "Tenant", selector: (r) => r.tenant_name, wrap: true },
        { name: "Plan", selector: (r) => r.plan_name || "—" },
        {
            name: "Period",
            selector: (r) => r.periods,
            cell: (r) => (
                <span className="text-capitalize">
                    {r.periods} {r.billing_cycle}
                </span>
            ),
        },
        {
            name: "Amount",
            selector: (r) => r.amount,
            cell: (r) => (
                <strong>
                    {Number(r.amount).toFixed(2)} {r.currency}
                </strong>
            ),
        },
        {
            name: "Method",
            selector: (r) => r.method,
            cell: (r) => (r.method || "").replace(/_/g, " "),
        },
        { name: "Reference", selector: (r) => r.reference || "—", wrap: true },
        {
            name: "Proof",
            cell: (r) =>
                r.proof_url ? (
                    <a
                        href={r.proof_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="btn btn-sm btn-outline-secondary"
                    >
                        View
                    </a>
                ) : (
                    <span className="text-muted small">—</span>
                ),
        },
        { name: "Status", selector: (r) => r.status, cell: (r) => statusBadge(r.status) },
        {
            name: "Paid / Valid To",
            selector: (r) => r.period_end,
            cell: (r) => fmtDate(r.period_end),
        },
        {
            name: "Action",
            right: true,
            cell: (r) =>
                r.status === "pending" ? (
                    <div className="d-flex gap-2">
                        <button
                            className="btn btn-sm btn-outline-success"
                            onClick={() => dispatch(confirmPayment(r.id))}
                        >
                            Confirm
                        </button>
                        <button
                            className="btn btn-sm btn-outline-danger"
                            onClick={() => onReject(r.id)}
                        >
                            Reject
                        </button>
                    </div>
                ) : (
                    <span className="text-muted small">—</span>
                ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Subscription Payments" />
            <ReactDataTable
                columns={columns}
                items={filtered}
                isLoading={isLoading}
                onChange={() => dispatch(fetchPlatformPayments())}
                pagination={false}
                isShowSearch
                AddButton={
                    <div className="d-flex gap-2 align-items-center">
                        <select
                            className="form-control form-control-sm"
                            style={{ width: 160 }}
                            value={statusFilter}
                            onChange={(e) => setStatusFilter(e.target.value)}
                        >
                            <option value="">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <Button
                            variant="light-primary"
                            onClick={() => dispatch(fetchPlatformPayments())}
                        >
                            Refresh
                        </Button>
                    </div>
                }
            />
        </MasterLayout>
    );
};

export default PlatformPayments;
