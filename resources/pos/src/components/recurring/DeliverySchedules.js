import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import {
    fetchDeliveries,
    updateDelivery,
} from "../../store/action/recurringAction";
import moment from "moment";

const STATUS_OPTIONS = [
    { value: "", label: "All" },
    { value: "scheduled", label: "Scheduled" },
    { value: "delivered", label: "Delivered" },
    { value: "partial", label: "Partial" },
    { value: "skipped", label: "Skipped" },
    { value: "cancelled", label: "Cancelled" },
];

const statusBadge = (s) => {
    const m = {
        scheduled: "bg-light-warning",
        delivered: "bg-light-success",
        partial: "bg-light-info",
        skipped: "bg-light-secondary",
        cancelled: "bg-light-danger",
    };
    return `badge text-capitalize ${m[s] || "bg-light-primary"}`;
};

const DeliverySchedules = () => {
    const dispatch = useDispatch();
    const deliveries = useSelector((s) => s.recurring?.deliveries || []);
    const isLoading = useSelector((s) => s.isLoading);
    const [status, setStatus] = useState("");
    const [route, setRoute] = useState("");
    const [date, setDate] = useState(moment().format("YYYY-MM-DD"));

    const load = () =>
        dispatch(
            fetchDeliveries({
                status: status || undefined,
                route_name: route || undefined,
                date_from: date,
                date_to: date,
            })
        );

    useEffect(() => {
        load();
    }, [status, route, date]);

    const mark = (id, st, extra = {}) =>
        dispatch(updateDelivery(id, { status: st, ...extra }));

    const columns = [
        {
            name: "Date",
            selector: (r) => r.scheduled_date,
            cell: (r) => moment(r.scheduled_date).format("ddd, DD MMM"),
        },
        { name: "Customer", selector: (r) => r.customer?.name || r.customer_id },
        { name: "Qty", selector: (r) => r.quantity },
        { name: "Extra", selector: (r) => r.extra_quantity || 0 },
        { name: "Route", selector: (r) => r.route_name || "-" },
        {
            name: "Status",
            cell: (r) => <span className={statusBadge(r.status)}>{r.status}</span>,
        },
        {
            name: "Action",
            right: true,
            cell: (r) =>
                r.status === "scheduled" || r.status === "partial" ? (
                    <div className="d-flex gap-1">
                        <button
                            className="btn btn-sm btn-outline-success"
                            onClick={() => mark(r.id, "delivered")}
                        >
                            Deliver
                        </button>
                        <button
                            className="btn btn-sm btn-outline-secondary"
                            onClick={() => mark(r.id, "skipped")}
                        >
                            Skip
                        </button>
                    </div>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Delivery Schedules" />
            <div className="row mb-3">
                <div className="col-md-3">
                    <label className="form-label small">Date</label>
                    <input
                        type="date"
                        className="form-control"
                        value={date}
                        onChange={(e) => setDate(e.target.value)}
                    />
                </div>
                <div className="col-md-3">
                    <label className="form-label small">Status</label>
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
                </div>
                <div className="col-md-3">
                    <label className="form-label small">Route</label>
                    <input
                        className="form-control"
                        value={route}
                        onChange={(e) => setRoute(e.target.value)}
                        placeholder="Filter by route"
                    />
                </div>
            </div>
            <ReactDataTable
                columns={columns}
                items={deliveries}
                isLoading={isLoading}
                pagination={false}
                isShowSearch
                onChange={load}
            />
        </MasterLayout>
    );
};

export default DeliverySchedules;
