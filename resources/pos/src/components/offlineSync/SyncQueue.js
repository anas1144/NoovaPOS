import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import {
    fetchSyncQueue,
    updateSyncQueueStatus,
} from "../../store/action/offlineDeviceAction";
import moment from "moment";

const STATUS_OPTIONS = [
    { value: "", label: "All" },
    { value: "queued", label: "Queued" },
    { value: "processing", label: "Processing" },
    { value: "synced", label: "Synced" },
    { value: "failed", label: "Failed" },
];

const statusBadge = (s) => {
    const map = {
        queued: "bg-light-warning",
        processing: "bg-light-info",
        synced: "bg-light-success",
        failed: "bg-light-danger",
    };
    return `badge text-capitalize ${map[s] || "bg-light-secondary"}`;
};

const SyncQueue = () => {
    const dispatch = useDispatch();
    const queue = useSelector((state) => state.offlineSync?.queue || []);
    const isLoading = useSelector((state) => state.isLoading);
    const [statusFilter, setStatusFilter] = useState("");

    useEffect(() => {
        dispatch(fetchSyncQueue(statusFilter ? { status: statusFilter } : {}));
    }, [statusFilter]);

    const items = (queue || []).map((q) => ({
        id: q.id,
        local_uuid: q.local_uuid,
        entity_type: q.entity_type,
        operation: q.operation,
        status: q.status,
        device: q.device?.name,
        batch: q.batch?.batch_uuid,
        attempted_at: q.attempted_at,
        synced_at: q.synced_at,
        error_message: q.error_message,
    }));

    const retry = (id) => {
        dispatch(updateSyncQueueStatus(id, { status: "queued" }));
    };

    const markSynced = (id) => {
        dispatch(updateSyncQueueStatus(id, { status: "synced" }));
    };

    const columns = [
        {
            name: "Local UUID",
            cell: (r) => (
                <code className="small">{(r.local_uuid || "").slice(0, 18)}</code>
            ),
        },
        { name: "Entity", selector: (r) => r.entity_type },
        {
            name: "Operation",
            selector: (r) => r.operation,
            cell: (r) => (
                <span className="badge bg-light-primary text-capitalize">
                    {r.operation}
                </span>
            ),
        },
        { name: "Device", selector: (r) => r.device || "-" },
        { name: "Batch", selector: (r) => (r.batch || "-").slice(0, 12) },
        {
            name: "Status",
            cell: (r) => <span className={statusBadge(r.status)}>{r.status}</span>,
        },
        {
            name: "Attempted",
            cell: (r) => (r.attempted_at ? moment(r.attempted_at).fromNow() : "-"),
        },
        {
            name: "Action",
            right: true,
            ignoreRowClick: true,
            allowOverflow: true,
            button: true,
            cell: (r) =>
                r.status === "failed" ? (
                    <button
                        className="btn btn-sm btn-outline-primary"
                        onClick={() => retry(r.id)}
                    >
                        Retry
                    </button>
                ) : r.status === "queued" || r.status === "processing" ? (
                    <button
                        className="btn btn-sm btn-outline-success"
                        onClick={() => markSynced(r.id)}
                    >
                        Mark Synced
                    </button>
                ) : (
                    <span className="text-muted">-</span>
                ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Sync Queue" />
            <div className="d-flex mb-3 align-items-center gap-2">
                <label className="form-label mb-0 me-2">Status:</label>
                <select
                    className="form-control w-auto"
                    value={statusFilter}
                    onChange={(e) => setStatusFilter(e.target.value)}
                >
                    {STATUS_OPTIONS.map((s) => (
                        <option key={s.value} value={s.value}>
                            {s.label}
                        </option>
                    ))}
                </select>
            </div>
            <ReactDataTable
                columns={columns}
                items={items}
                isLoading={isLoading}
                pagination={false}
                isShowSearch
                onChange={() => dispatch(fetchSyncQueue(statusFilter ? { status: statusFilter } : {}))}
            />
        </MasterLayout>
    );
};

export default SyncQueue;
