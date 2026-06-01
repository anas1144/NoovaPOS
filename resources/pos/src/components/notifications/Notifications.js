import React, { useEffect, useState } from "react";
import { Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL } from "../../constants";
import moment from "moment";

const CHANNELS = [
    { value: "", label: "All channels" },
    { value: "in_app", label: "In-App" },
    { value: "email", label: "Email" },
    { value: "sms", label: "SMS" },
    { value: "whatsapp", label: "WhatsApp" },
    { value: "push", label: "Push" },
];

const STATUSES = [
    { value: "", label: "All statuses" },
    { value: "queued", label: "Queued" },
    { value: "sending", label: "Sending" },
    { value: "sent", label: "Sent" },
    { value: "failed", label: "Failed" },
    { value: "read", label: "Read" },
];

const channelBadge = (c) => {
    const m = {
        in_app: "bg-light-primary",
        email: "bg-light-info",
        sms: "bg-light-warning",
        whatsapp: "bg-light-success",
        push: "bg-light-secondary",
    };
    return `badge text-capitalize ${m[c] || "bg-light-secondary"}`;
};

const statusBadge = (s) => {
    const m = {
        queued: "bg-light-warning",
        sending: "bg-light-info",
        sent: "bg-light-success",
        failed: "bg-light-danger",
        read: "bg-light-secondary",
    };
    return `badge text-capitalize ${m[s] || "bg-light-primary"}`;
};

const Notifications = () => {
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(false);
    const [channel, setChannel] = useState("");
    const [status, setStatus] = useState("");
    const [unreadCount, setUnreadCount] = useState(0);

    const load = async () => {
        setLoading(true);
        try {
            const res = await apiConfig.get(apiBaseURL.NOTIFICATIONS, {
                params: {
                    channel: channel || undefined,
                    status: status || undefined,
                },
            });
            setItems(res.data?.data?.data || res.data?.data || []);
            const c = await apiConfig.get(
                apiBaseURL.NOTIFICATIONS + "/unread-count"
            );
            setUnreadCount(c.data?.data?.count || 0);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, [channel, status]);

    const markRead = async (id) => {
        await apiConfig.patch(apiBaseURL.NOTIFICATIONS + "/" + id + "/read");
        load();
    };

    const markAllRead = async () => {
        await apiConfig.post(apiBaseURL.NOTIFICATIONS + "/mark-all-read");
        load();
    };

    const columns = [
        {
            name: "When",
            cell: (r) =>
                r.created_at ? moment(r.created_at).fromNow() : "—",
        },
        {
            name: "Channel",
            cell: (r) => (
                <span className={channelBadge(r.channel)}>
                    {(r.channel || "").replace("_", " ")}
                </span>
            ),
        },
        {
            name: "Event",
            selector: (r) => r.event,
            cell: (r) => (
                <span className="badge bg-light-primary text-capitalize">
                    {(r.event || "").replace(/_/g, " ")}
                </span>
            ),
        },
        { name: "Subject", selector: (r) => r.subject || "—" },
        { name: "Recipient", selector: (r) => r.recipient || "—" },
        {
            name: "Status",
            cell: (r) => <span className={statusBadge(r.status)}>{r.status}</span>,
        },
        {
            name: "Action",
            right: true,
            cell: (r) =>
                r.channel === "in_app" && !r.read_at ? (
                    <button
                        className="btn btn-sm btn-outline-primary"
                        onClick={() => markRead(r.id)}
                    >
                        Mark read
                    </button>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Notifications" />
            <div className="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h4 className="mb-0">Notifications</h4>
                    {unreadCount > 0 && (
                        <small className="text-muted">
                            {unreadCount} unread in-app{" "}
                            <button
                                className="btn btn-link btn-sm p-0"
                                onClick={markAllRead}
                            >
                                mark all read
                            </button>
                        </small>
                    )}
                </div>
                <div className="d-flex gap-2">
                    <select
                        className="form-control"
                        value={channel}
                        onChange={(e) => setChannel(e.target.value)}
                    >
                        {CHANNELS.map((c) => (
                            <option key={c.value} value={c.value}>
                                {c.label}
                            </option>
                        ))}
                    </select>
                    <select
                        className="form-control"
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                    >
                        {STATUSES.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>
                    <Button variant="outline-primary" onClick={load}>
                        Refresh
                    </Button>
                    <Button
                        variant="primary"
                        onClick={async () => {
                            await apiConfig.post(
                                apiBaseURL.NOTIFICATIONS + "/dispatch"
                            );
                            load();
                        }}
                    >
                        Send queued
                    </Button>
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

export default Notifications;
