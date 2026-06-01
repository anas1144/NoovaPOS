import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Modal, Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import {
    fetchCustomerSubscriptions,
    saveCustomerSubscription,
    pauseSubscription,
    resumeSubscription,
    fetchRecurringPlans,
    generateInvoiceForSubscription,
} from "../../store/action/recurringAction";
import { fetchCustomer } from "../../store/action/customerAction";
import moment from "moment";

const DAYS = [
    { v: 1, label: "Mon" },
    { v: 2, label: "Tue" },
    { v: 3, label: "Wed" },
    { v: 4, label: "Thu" },
    { v: 5, label: "Fri" },
    { v: 6, label: "Sat" },
    { v: 7, label: "Sun" },
];

const SubForm = ({ show, data, onHide }) => {
    const dispatch = useDispatch();
    const customers = useSelector((s) => s.customers || []);
    const plans = useSelector((s) => s.recurring?.plans || []);
    const [f, setF] = useState({
        customer_id: "",
        recurring_plan_id: "",
        default_quantity: 1,
        schedule_days: [1, 3, 5],
        route_name: "",
        delivery_address: "",
        start_date: moment().format("YYYY-MM-DD"),
        deposit_paid: 0,
        notes: "",
    });

    useEffect(() => {
        if (show) {
            dispatch(fetchRecurringPlans());
            if (!customers || customers.length === 0) {
                dispatch && dispatch(fetchCustomer && fetchCustomer());
            }
        }
    }, [show]);

    useEffect(() => {
        setF({
            customer_id: data?.customer_id || "",
            recurring_plan_id: data?.recurring_plan_id || "",
            default_quantity: data?.default_quantity || 1,
            schedule_days: data?.schedule_days || [1, 3, 5],
            route_name: data?.route_name || "",
            delivery_address: data?.delivery_address || "",
            start_date:
                data?.start_date || moment().format("YYYY-MM-DD"),
            deposit_paid: data?.deposit_paid || 0,
            notes: data?.notes || "",
        });
    }, [data, show]);

    const setVal = (k) => (e) => setF((s) => ({ ...s, [k]: e.target.value }));

    const toggleDay = (d) =>
        setF((s) => ({
            ...s,
            schedule_days: s.schedule_days.includes(d)
                ? s.schedule_days.filter((x) => x !== d)
                : [...s.schedule_days, d].sort((a, b) => a - b),
        }));

    const submit = () => dispatch(saveCustomerSubscription(f, data?.id, onHide));

    return (
        <Modal show={show} onHide={onHide} size="lg">
            <Modal.Header closeButton>
                <Modal.Title>
                    {data?.id ? "Edit Subscription" : "Create Subscription"}
                </Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Customer</label>
                        <select
                            className="form-control"
                            value={f.customer_id || ""}
                            onChange={setVal("customer_id")}
                        >
                            <option value="">-- Select customer --</option>
                            {(customers || []).map((c) => (
                                <option
                                    key={c.id}
                                    value={c.id || c?.attributes?.id}
                                >
                                    {c?.attributes?.name || c.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Recurring Plan</label>
                        <select
                            className="form-control"
                            value={f.recurring_plan_id || ""}
                            onChange={setVal("recurring_plan_id")}
                        >
                            <option value="">-- Select plan --</option>
                            {plans.map((p) => (
                                <option key={p.id} value={p.id}>
                                    {p.name} ({p.billing_cycle})
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Default Quantity</label>
                        <input
                            type="number"
                            step="0.001"
                            className="form-control"
                            value={f.default_quantity}
                            onChange={setVal("default_quantity")}
                        />
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Route</label>
                        <input
                            className="form-control"
                            value={f.route_name}
                            onChange={setVal("route_name")}
                            placeholder="e.g. Lahore Route 1"
                        />
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Start Date</label>
                        <input
                            type="date"
                            className="form-control"
                            value={f.start_date}
                            onChange={setVal("start_date")}
                        />
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-label">Schedule (days)</label>
                        <div className="d-flex flex-wrap gap-2">
                            {DAYS.map((d) => (
                                <label
                                    key={d.v}
                                    className={`badge cursor-pointer p-2 ${
                                        f.schedule_days.includes(d.v)
                                            ? "bg-primary"
                                            : "bg-light-secondary text-dark"
                                    }`}
                                    onClick={() => toggleDay(d.v)}
                                >
                                    {d.label}
                                </label>
                            ))}
                        </div>
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-label">Delivery Address</label>
                        <input
                            className="form-control"
                            value={f.delivery_address}
                            onChange={setVal("delivery_address")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Deposit Paid</label>
                        <input
                            type="number"
                            step="0.01"
                            className="form-control"
                            value={f.deposit_paid}
                            onChange={setVal("deposit_paid")}
                        />
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-label">Notes</label>
                        <textarea
                            className="form-control"
                            value={f.notes}
                            onChange={setVal("notes")}
                        />
                    </div>
                </div>
            </Modal.Body>
            <Modal.Footer>
                <Button onClick={submit}>Save</Button>
                <Button variant="secondary" onClick={onHide}>
                    Cancel
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

const statusBadge = (s) => {
    const m = {
        active: "bg-light-success",
        paused: "bg-light-warning",
        ended: "bg-light-secondary",
    };
    return `badge text-capitalize ${m[s] || "bg-light-primary"}`;
};

const CustomerSubscriptions = () => {
    const dispatch = useDispatch();
    const subs = useSelector((s) => s.recurring?.subscriptions || []);
    const isLoading = useSelector((s) => s.isLoading);
    const [show, setShow] = useState(false);
    const [editData, setEditData] = useState(null);

    useEffect(() => {
        dispatch(fetchCustomerSubscriptions());
    }, []);

    const genInvoice = (row) => {
        const today = moment();
        dispatch(
            generateInvoiceForSubscription(
                row.id,
                today.clone().startOf("month").format("YYYY-MM-DD"),
                today.format("YYYY-MM-DD")
            )
        );
    };

    const columns = [
        { name: "Customer", selector: (r) => r.customer?.name || r.customer_id },
        { name: "Plan", selector: (r) => r.plan?.name || "-" },
        {
            name: "Schedule",
            cell: (r) =>
                (r.schedule_days || [])
                    .map((d) => DAYS.find((x) => x.v === d)?.label)
                    .join(", ") || "-",
        },
        { name: "Qty/Day", selector: (r) => r.default_quantity },
        {
            name: "Bottles w/customer",
            selector: (r) => r.bottles_with_customer || 0,
            cell: (r) => (
                <span className="badge bg-light-info">
                    {r.bottles_with_customer || 0}
                </span>
            ),
        },
        { name: "Route", selector: (r) => r.route_name || "-" },
        {
            name: "Status",
            cell: (r) => <span className={statusBadge(r.status)}>{r.status}</span>,
        },
        {
            name: "Action",
            right: true,
            cell: (r) => (
                <div className="d-flex gap-1">
                    <button
                        className="btn btn-sm btn-outline-primary"
                        onClick={() => {
                            setEditData(r);
                            setShow(true);
                        }}
                    >
                        Edit
                    </button>
                    {r.status === "active" ? (
                        <button
                            className="btn btn-sm btn-outline-warning"
                            onClick={() => dispatch(pauseSubscription(r.id))}
                        >
                            Pause
                        </button>
                    ) : (
                        <button
                            className="btn btn-sm btn-outline-success"
                            onClick={() => dispatch(resumeSubscription(r.id))}
                        >
                            Resume
                        </button>
                    )}
                    <button
                        className="btn btn-sm btn-outline-info"
                        onClick={() => genInvoice(r)}
                    >
                        Generate Invoice
                    </button>
                </div>
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Customer Subscriptions" />
            <ReactDataTable
                columns={columns}
                items={subs}
                isLoading={isLoading}
                pagination={false}
                isShowSearch
                onChange={() => dispatch(fetchCustomerSubscriptions())}
                AddButton={
                    <div className="text-end">
                        <Button
                            onClick={() => {
                                setEditData(null);
                                setShow(true);
                            }}
                        >
                            + New Subscription
                        </Button>
                    </div>
                }
            />
            <SubForm
                show={show}
                data={editData}
                onHide={() => setShow(false)}
            />
        </MasterLayout>
    );
};

export default CustomerSubscriptions;
