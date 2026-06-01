import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Modal, Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import {
    fetchPlatformPlans,
    addPlan,
    editPlan,
} from "../../store/action/platformAction";

const FEATURE_FLAGS = [
    "retail_pos",
    "restaurant_pos",
    "pharmacy_pos",
    "water_supply_pos",
    "fbr_integration",
    "offline_mode",
    "accounting",
    "hr_module",
    "crm_module",
    "ai_reports",
    "whatsapp",
    "api_access",
];

const PlanForm = ({ show, isEdit, data, onHide }) => {
    const dispatch = useDispatch();
    const [form, setForm] = useState({
        name: "",
        slug: "",
        description: "",
        price: 0,
        billing_cycle: "monthly",
        trial_days: 0,
        max_stores: 0,
        max_shops: 0,
        max_registers: 0,
        max_users: 0,
        max_products: 0,
        status: true,
        features: [],
    });

    useEffect(() => {
        setForm({
            name: data?.name || "",
            slug: data?.slug || "",
            description: data?.description || "",
            price: data?.price || 0,
            billing_cycle: data?.billing_cycle || "monthly",
            trial_days: data?.trial_days || 0,
            max_stores: data?.max_stores || 0,
            max_shops: data?.max_shops || 0,
            max_registers: data?.max_registers || 0,
            max_users: data?.max_users || 0,
            max_products: data?.max_products || 0,
            status: data?.status !== false,
            features: data?.features || [],
        });
    }, [data, show]);

    const setF = (k) => (e) =>
        setForm((f) => ({
            ...f,
            [k]: e.target.type === "checkbox" ? e.target.checked : e.target.value,
        }));

    const toggleFeature = (f) =>
        setForm((s) => ({
            ...s,
            features: s.features.includes(f)
                ? s.features.filter((x) => x !== f)
                : [...s.features, f],
        }));

    const submit = () => {
        const payload = { ...form };
        if (isEdit) {
            dispatch(editPlan(data.id, payload, onHide));
        } else {
            dispatch(addPlan(payload, onHide));
        }
    };

    return (
        <Modal show={show} onHide={onHide} size="lg">
            <Modal.Header closeButton>
                <Modal.Title>{isEdit ? "Edit Plan" : "Create Plan"}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Name</label>
                        <input
                            type="text"
                            className="form-control"
                            value={form.name}
                            onChange={setF("name")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Slug</label>
                        <input
                            type="text"
                            className="form-control"
                            value={form.slug}
                            onChange={setF("slug")}
                            placeholder="auto"
                        />
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-label">Description</label>
                        <textarea
                            className="form-control"
                            value={form.description}
                            onChange={setF("description")}
                        />
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Price</label>
                        <input
                            type="number"
                            step="0.01"
                            className="form-control"
                            value={form.price}
                            onChange={setF("price")}
                        />
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Billing Cycle</label>
                        <select
                            className="form-control"
                            value={form.billing_cycle}
                            onChange={setF("billing_cycle")}
                        >
                            <option value="free">Free</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Trial Days</label>
                        <input
                            type="number"
                            className="form-control"
                            value={form.trial_days}
                            onChange={setF("trial_days")}
                        />
                    </div>
                    {["max_stores", "max_shops", "max_registers", "max_users", "max_products"].map(
                        (k) => (
                            <div key={k} className="col-md-4 mb-3">
                                <label className="form-label text-capitalize">
                                    {k.replace("_", " ")}
                                </label>
                                <input
                                    type="number"
                                    className="form-control"
                                    value={form[k]}
                                    onChange={setF(k)}
                                />
                            </div>
                        )
                    )}
                    <div className="col-md-12 mb-3">
                        <label className="form-label">Features</label>
                        <div className="d-flex flex-wrap gap-2">
                            {FEATURE_FLAGS.map((f) => (
                                <label
                                    key={f}
                                    className={`badge ${
                                        form.features.includes(f)
                                            ? "bg-primary"
                                            : "bg-light-secondary text-dark"
                                    } p-2 cursor-pointer`}
                                    onClick={() => toggleFeature(f)}
                                >
                                    <input
                                        type="checkbox"
                                        className="d-none"
                                        checked={form.features.includes(f)}
                                        readOnly
                                    />
                                    {f.replace(/_/g, " ")}
                                </label>
                            ))}
                        </div>
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-check form-switch form-switch-sm">
                            <input
                                type="checkbox"
                                className="form-check-input me-3"
                                checked={!!form.status}
                                onChange={setF("status")}
                            />
                            <span>Plan is active</span>
                        </label>
                    </div>
                </div>
            </Modal.Body>
            <Modal.Footer>
                <Button variant="primary" onClick={submit}>
                    {isEdit ? "Update" : "Create"}
                </Button>
                <Button variant="secondary" onClick={onHide}>
                    Cancel
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

const PlatformPlans = () => {
    const dispatch = useDispatch();
    const plans = useSelector((s) => s.platform?.plans || []);
    const isLoading = useSelector((s) => s.isLoading);
    const [show, setShow] = useState(false);
    const [editData, setEditData] = useState(null);

    useEffect(() => {
        dispatch(fetchPlatformPlans());
    }, []);

    const openCreate = () => {
        setEditData(null);
        setShow(true);
    };
    const openEdit = (p) => {
        setEditData(p);
        setShow(true);
    };

    const columns = [
        { name: "Name", selector: (r) => r.name },
        { name: "Slug", selector: (r) => r.slug },
        {
            name: "Price",
            selector: (r) => r.price,
            cell: (r) => (
                <span className="fw-semibold">
                    {Number(r.price).toLocaleString()}
                </span>
            ),
        },
        {
            name: "Cycle",
            selector: (r) => r.billing_cycle,
            cell: (r) => (
                <span className="badge bg-light-primary text-capitalize">
                    {r.billing_cycle}
                </span>
            ),
        },
        { name: "Trial", selector: (r) => r.trial_days, cell: (r) => `${r.trial_days || 0}d` },
        { name: "Max Shops", selector: (r) => r.max_shops },
        { name: "Max Users", selector: (r) => r.max_users },
        {
            name: "Status",
            cell: (r) => (
                <span className={`badge ${r.status ? "bg-light-success" : "bg-light-secondary"}`}>
                    {r.status ? "Active" : "Disabled"}
                </span>
            ),
        },
        {
            name: "Action",
            right: true,
            cell: (r) => (
                <button
                    className="btn btn-sm btn-outline-primary"
                    onClick={() => openEdit(r)}
                >
                    Edit
                </button>
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Plans" />
            <ReactDataTable
                columns={columns}
                items={plans}
                isLoading={isLoading}
                pagination={false}
                isShowSearch
                onChange={() => dispatch(fetchPlatformPlans())}
                AddButton={
                    <div className="text-end">
                        <Button onClick={openCreate}>+ New Plan</Button>
                    </div>
                }
            />
            <PlanForm
                show={show}
                isEdit={!!editData}
                data={editData}
                onHide={() => setShow(false)}
            />
        </MasterLayout>
    );
};

export default PlatformPlans;
