import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Modal, Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import {
    fetchPlatformTenants,
    changeTenantStatus,
    createTenant,
    assignTenantPlan,
    fetchPlatformPlans,
    toggleTenantSeparateDb,
    grantTenantAddons,
} from "../../store/action/platformAction";

const GrantAddonsModal = ({ show, tenant, onHide }) => {
    const dispatch = useDispatch();
    const [qty, setQty] = useState({ shops: 0, users: 0, products: 0 });

    useEffect(() => {
        if (show) setQty({ shops: 0, users: 0, products: 0 });
    }, [show]);

    const submit = () => {
        dispatch(
            grantTenantAddons(
                tenant?.id,
                {
                    shops: Number(qty.shops) || 0,
                    users: Number(qty.users) || 0,
                    products: Number(qty.products) || 0,
                },
                onHide
            )
        );
    };

    return (
        <Modal show={show} onHide={onHide}>
            <Modal.Header closeButton>
                <Modal.Title>Grant add-ons — {tenant?.store_name}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <p className="text-muted">
                    Directly raise this tenant's limits (no payment required).
                </p>
                <div className="row">
                    {["shops", "users", "products"].map((k) => (
                        <div className="col-4 mb-3" key={k}>
                            <label className="form-label text-capitalize">{k}</label>
                            <input
                                type="number"
                                min="0"
                                className="form-control"
                                value={qty[k]}
                                onChange={(e) => setQty((q) => ({ ...q, [k]: e.target.value }))}
                            />
                        </div>
                    ))}
                </div>
            </Modal.Body>
            <Modal.Footer>
                <Button variant="primary" onClick={submit}>Grant</Button>
                <Button variant="secondary" onClick={onHide}>Cancel</Button>
            </Modal.Footer>
        </Modal>
    );
};

const CreateTenantModal = ({ show, onHide }) => {
    const dispatch = useDispatch();
    const [form, setForm] = useState({
        business_name: "",
        owner_first_name: "",
        owner_last_name: "",
        owner_email: "",
        owner_phone: "",
        owner_password: "",
        subdomain: "",
        plan_ids: [],
    });
    const plans = useSelector((s) => s.platform?.plans || []);

    // A tenant can subscribe to several plans (one per shop type); each plan
    // provisions a store of its shop type. Only show per-shop-type plans here.
    const typePlans = plans.filter((p) => p.shop_type);

    const togglePlan = (id) =>
        setForm((f) => {
            const has = f.plan_ids.includes(id);
            return { ...f, plan_ids: has ? f.plan_ids.filter((x) => x !== id) : [...f.plan_ids, id] };
        });

    useEffect(() => {
        if (show) dispatch(fetchPlatformPlans());
    }, [show]);

    const setF = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));

    const submit = () => {
        dispatch(createTenant(form, onHide));
    };

    return (
        <Modal show={show} onHide={onHide} size="lg">
            <Modal.Header closeButton>
                <Modal.Title>Onboard new tenant</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Business Name</label>
                        <input
                            type="text"
                            className="form-control"
                            value={form.business_name}
                            onChange={setF("business_name")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Subdomain</label>
                        <input
                            type="text"
                            className="form-control"
                            value={form.subdomain}
                            onChange={setF("subdomain")}
                            placeholder="e.g. acme"
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Owner First Name</label>
                        <input
                            type="text"
                            className="form-control"
                            value={form.owner_first_name}
                            onChange={setF("owner_first_name")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Owner Last Name</label>
                        <input
                            type="text"
                            className="form-control"
                            value={form.owner_last_name}
                            onChange={setF("owner_last_name")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Owner Email</label>
                        <input
                            type="email"
                            className="form-control"
                            value={form.owner_email}
                            onChange={setF("owner_email")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Owner Phone</label>
                        <input
                            type="text"
                            className="form-control"
                            value={form.owner_phone}
                            onChange={setF("owner_phone")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Owner Password</label>
                        <input
                            type="password"
                            className="form-control"
                            value={form.owner_password}
                            onChange={setF("owner_password")}
                        />
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-label">Plans (one per shop type)</label>
                        <small className="text-muted d-block mb-2">
                            Pick one or more. Each selected plan creates a store of its
                            shop type. The first becomes the default store.
                        </small>
                        <div className="row">
                            {typePlans.length === 0 && (
                                <div className="col-12 text-muted">No per-shop-type plans found.</div>
                            )}
                            {typePlans.map((p) => (
                                <div className="col-md-6 mb-2" key={p.id}>
                                    <label className="d-flex align-items-start gap-2 border rounded p-2 h-100" style={{ cursor: "pointer" }}>
                                        <input
                                            type="checkbox"
                                            className="form-check-input mt-1"
                                            checked={form.plan_ids.includes(p.id)}
                                            onChange={() => togglePlan(p.id)}
                                        />
                                        <span>
                                            <span className="fw-semibold d-block">{p.name}</span>
                                            <span className="text-muted small">
                                                {(p.shop_type || "").replace("_", " ")} · {p.billing_cycle} · ${Number(p.price).toFixed(0)}
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </Modal.Body>
            <Modal.Footer>
                <Button variant="primary" onClick={submit}>
                    Create Tenant
                </Button>
                <Button variant="secondary" onClick={onHide}>
                    Cancel
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

const AssignPlanModal = ({ show, tenant, onHide }) => {
    const dispatch = useDispatch();
    const plans = useSelector((s) => s.platform?.plans || []);
    const [planId, setPlanId] = useState("");
    const [status, setStatus] = useState("active");
    const [billingCycle, setBillingCycle] = useState("monthly");
    const [periods, setPeriods] = useState(1);

    useEffect(() => {
        if (show) dispatch(fetchPlatformPlans());
    }, [show]);

    const submit = () => {
        if (!planId) return;
        dispatch(
            assignTenantPlan(tenant?.id, {
                plan_id: planId,
                status,
                billing_cycle: billingCycle,
                periods: Number(periods) || 1,
            })
        );
        onHide();
    };

    return (
        <Modal show={show} onHide={onHide}>
            <Modal.Header closeButton>
                <Modal.Title>Assign plan to {tenant?.store_name}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="mb-3">
                    <label className="form-label">Plan</label>
                    <select
                        className="form-control"
                        value={planId}
                        onChange={(e) => setPlanId(e.target.value)}
                    >
                        <option value="">-- Select plan --</option>
                        {plans.map((p) => (
                            <option key={p.id} value={p.id}>
                                {p.name} — {p.price} / {p.billing_cycle}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="mb-3">
                    <label className="form-label">Status</label>
                    <select
                        className="form-control"
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                    >
                        <option value="trialing">Trialing</option>
                        <option value="active">Active</option>
                        <option value="past_due">Past Due</option>
                        <option value="suspended">Suspended</option>
                        <option value="canceled">Canceled</option>
                    </select>
                </div>
                <div className="row">
                    <div className="col-7 mb-3">
                        <label className="form-label">Billing Cycle</label>
                        <select
                            className="form-control"
                            value={billingCycle}
                            onChange={(e) => setBillingCycle(e.target.value)}
                        >
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    <div className="col-5 mb-3">
                        <label className="form-label">
                            {billingCycle === "yearly" ? "Years" : "Months"}
                        </label>
                        <input
                            type="number"
                            min="1"
                            max="60"
                            className="form-control"
                            value={periods}
                            onChange={(e) => setPeriods(e.target.value)}
                        />
                    </div>
                    <small className="text-muted mb-2">
                        Sets the paid-through date (valid until) for the
                        subscription.
                    </small>
                </div>
            </Modal.Body>
            <Modal.Footer>
                <Button variant="primary" onClick={submit}>
                    Assign
                </Button>
                <Button variant="secondary" onClick={onHide}>
                    Cancel
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

const PlatformTenants = () => {
    const dispatch = useDispatch();
    const tenants = useSelector((s) => s.platform?.tenants || []);
    const isLoading = useSelector((s) => s.isLoading);
    const [showCreate, setShowCreate] = useState(false);
    const [planTenant, setPlanTenant] = useState(null);
    const [addonTenant, setAddonTenant] = useState(null);

    useEffect(() => {
        dispatch(fetchPlatformTenants());
    }, []);

    const toggleStatus = (row) => {
        dispatch(changeTenantStatus(row.id, !row.status));
    };

    const columns = [
        { name: "Tenant ID", selector: (r) => r.id },
        { name: "Business", selector: (r) => r.store_name || "-" },
        {
            name: "Stores / Shops",
            selector: (r) => r.stores_count,
            cell: (r) => (
                <span className="badge bg-light-info">
                    {r.stores_count ?? (r.stores ? r.stores.length : 0)} store(s)
                </span>
            ),
        },
        { name: "Domain", selector: (r) => r.domain || "-" },
        { name: "Owner", selector: (r) => r.owner_email || "-" },
        {
            name: "Plan",
            selector: (r) => r.plan_name,
            cell: (r) => (
                <span className="badge bg-light-primary">
                    {r.plan_name || "No plan"}
                </span>
            ),
        },
        {
            name: "Subscription",
            selector: (r) => r.subscription_status,
            cell: (r) => (
                <span className="badge bg-light-success text-capitalize">
                    {r.subscription_status || "-"}
                </span>
            ),
        },
        {
            name: "Separate DB",
            cell: (r) => (
                <label className="form-check form-switch form-switch-sm" title="Run this tenant on its own isolated database">
                    <input
                        type="checkbox"
                        checked={!!r.uses_separate_db}
                        onChange={() => dispatch(toggleTenantSeparateDb(r.id, !r.uses_separate_db))}
                        className="me-3 form-check-input cursor-pointer"
                    />
                    <div className="control__indicator" />
                </label>
            ),
        },
        {
            name: "Status",
            cell: (r) => (
                <label className="form-check form-switch form-switch-sm">
                    <input
                        type="checkbox"
                        checked={!!r.status}
                        onChange={() => toggleStatus(r)}
                        className="me-3 form-check-input cursor-pointer"
                    />
                    <div className="control__indicator" />
                </label>
            ),
        },
        {
            name: "Action",
            right: true,
            cell: (r) => (
                <div className="d-flex gap-2">
                    <button
                        className="btn btn-sm btn-outline-primary"
                        onClick={() => setPlanTenant(r)}
                    >
                        Assign Plan
                    </button>
                    <button
                        className="btn btn-sm btn-outline-secondary"
                        onClick={() => setAddonTenant(r)}
                    >
                        Add-ons
                    </button>
                </div>
            ),
        },
    ];

    // Expandable row: shows the tenant's stores / shops.
    const TenantStores = ({ data }) => {
        const stores = data?.stores || [];
        if (!stores.length) {
            return (
                <div className="px-5 py-3 text-muted">
                    No stores / shops for this tenant.
                </div>
            );
        }
        return (
            <div className="px-5 py-3">
                <table className="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Store ID</th>
                            <th>Store / Shop Name</th>
                            <th>Default</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {stores.map((s) => (
                            <tr key={s.id}>
                                <td>{s.id}</td>
                                <td>{s.name}</td>
                                <td>
                                    {s.is_default ? (
                                        <span className="badge bg-light-primary text-primary">Primary</span>
                                    ) : (
                                        "-"
                                    )}
                                </td>
                                <td>
                                    <span
                                        className={`badge text-capitalize ${
                                            s.status
                                                ? "bg-light-success text-success"
                                                : "bg-light-danger text-danger"
                                        }`}
                                    >
                                        {s.status ? "active" : "inactive"}
                                    </span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        );
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Tenants" />
            <ReactDataTable
                columns={columns}
                items={tenants || []}
                isLoading={isLoading}
                onChange={() => dispatch(fetchPlatformTenants())}
                pagination={false}
                isShowSearch
                expandableRows
                expandableRowsComponent={TenantStores}
                AddButton={
                    <div className="text-end">
                        <Button onClick={() => setShowCreate(true)}>
                            + New Tenant
                        </Button>
                    </div>
                }
            />
            <CreateTenantModal
                show={showCreate}
                onHide={() => setShowCreate(false)}
            />
            <AssignPlanModal
                show={!!planTenant}
                tenant={planTenant}
                onHide={() => setPlanTenant(null)}
            />
            <GrantAddonsModal
                show={!!addonTenant}
                tenant={addonTenant}
                onHide={() => setAddonTenant(null)}
            />
        </MasterLayout>
    );
};

export default PlatformTenants;
