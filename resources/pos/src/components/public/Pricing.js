import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { Card, Row, Col, Button, Modal } from "react-bootstrap";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
    faStore,
    faCheck,
    faTimes,
    faBoltLightning,
    faShop,
    faUtensils,
    faTruckMoving,
    faPills,
    faIndustry,
    faGlobe,
} from "@fortawesome/free-solid-svg-icons";
import apiConfig from "../../config/apiConfig";
import TabTitle from "../../shared/tab-title/TabTitle";

const FEATURE_LABELS = {
    retail_pos: "Retail POS",
    restaurant_pos: "Restaurant POS",
    pharmacy_pos: "Pharmacy POS",
    water_supply_pos: "Water Supply POS",
    fbr_integration: "FBR Digital Invoicing",
    offline_mode: "Offline Mode",
    accounting: "Accounting",
    hr_module: "HR Module",
    crm_module: "CRM Module",
    ai_reports: "AI Reports",
    whatsapp: "WhatsApp Integration",
    api_access: "API Access",
};

const BUSINESS_TYPES = [
    { icon: faShop, label: "Retail", text: "Inventory, barcode, variants, GRN" },
    {
        icon: faUtensils,
        label: "Restaurant",
        text: "Tables, halls, KOT, waiters, kitchen display",
    },
    {
        icon: faPills,
        label: "Pharmacy",
        text: "Batches, expiry, prescription tracking",
    },
    {
        icon: faTruckMoving,
        label: "Water Supply",
        text: "Routes, bottle deposits, recurring billing",
    },
    {
        icon: faIndustry,
        label: "Distribution",
        text: "Multi-warehouse, stock transfers, supplier ledger",
    },
    {
        icon: faGlobe,
        label: "Custom",
        text: "Mix & match modules per shop",
    },
];

const DemoModal = ({ show, onHide }) => {
    const [form, setForm] = useState({
        name: "",
        email: "",
        phone: "",
        business_name: "",
        business_type: "Retail",
        message: "",
    });
    const [status, setStatus] = useState(null);
    const [loading, setLoading] = useState(false);

    const set = (k) => (e) => setForm((s) => ({ ...s, [k]: e.target.value }));

    const submit = async () => {
        setLoading(true);
        try {
            await apiConfig.post("public/demo-request", form);
            setStatus({ ok: true, msg: "Thanks — our team will reach out shortly." });
            setForm({
                name: "",
                email: "",
                phone: "",
                business_name: "",
                business_type: "Retail",
                message: "",
            });
        } catch (e) {
            setStatus({
                ok: false,
                msg: e?.response?.data?.message || "Could not submit. Please try again.",
            });
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal show={show} onHide={onHide} size="lg">
            <Modal.Header closeButton>
                <Modal.Title>Request a Demo</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                {status && (
                    <div
                        className={`alert ${
                            status.ok ? "alert-success" : "alert-danger"
                        }`}
                    >
                        {status.msg}
                    </div>
                )}
                <div className="row">
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Your Name</label>
                        <input
                            className="form-control"
                            value={form.name}
                            onChange={set("name")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Email</label>
                        <input
                            type="email"
                            className="form-control"
                            value={form.email}
                            onChange={set("email")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Phone</label>
                        <input
                            className="form-control"
                            value={form.phone}
                            onChange={set("phone")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Business Name</label>
                        <input
                            className="form-control"
                            value={form.business_name}
                            onChange={set("business_name")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Business Type</label>
                        <select
                            className="form-control"
                            value={form.business_type}
                            onChange={set("business_type")}
                        >
                            {BUSINESS_TYPES.map((t) => (
                                <option key={t.label} value={t.label}>
                                    {t.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-label">What are you looking for?</label>
                        <textarea
                            className="form-control"
                            rows="3"
                            value={form.message}
                            onChange={set("message")}
                        />
                    </div>
                </div>
            </Modal.Body>
            <Modal.Footer>
                <Button variant="primary" onClick={submit} disabled={loading}>
                    {loading ? "Sending..." : "Send"}
                </Button>
                <Button variant="secondary" onClick={onHide}>
                    Close
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

const Pricing = () => {
    const [plans, setPlans] = useState([]);
    const [cycle, setCycle] = useState("monthly");
    const [showDemo, setShowDemo] = useState(false);

    useEffect(() => {
        apiConfig
            .get("public/plans")
            .then((res) => setPlans(res.data?.data || []))
            .catch(() => setPlans([]));
    }, []);

    const visiblePlans = plans.filter(
        (p) => p.billing_cycle === cycle || p.billing_cycle === "free"
    );

    const numberFmt = (n) => Number(n || 0).toLocaleString();

    return (
        <div className="bg-light min-vh-100 pb-5">
            <TabTitle title="Pricing — Noova POS SaaS" />

            {/* Hero */}
            <div className="bg-primary text-white py-5">
                <div className="container">
                    <nav className="d-flex justify-content-between align-items-center mb-5">
                        <Link
                            to="/login"
                            className="text-white fw-bold fs-4 text-decoration-none"
                        >
                            <FontAwesomeIcon icon={faStore} className="me-2" />
                            Noova POS
                        </Link>
                        <div className="d-flex gap-2">
                            <Link
                                to="/login"
                                className="btn btn-light btn-sm fw-semibold"
                            >
                                Sign In
                            </Link>
                            <Link
                                to="/register-tenant"
                                className="btn btn-warning btn-sm fw-semibold"
                            >
                                Start Free Trial
                            </Link>
                        </div>
                    </nav>
                    <div className="text-center py-4">
                        <h1 className="display-4 fw-bold mb-3">
                            Run any business — from one cloud POS
                        </h1>
                        <p className="lead opacity-75 mb-4 mx-auto" style={{ maxWidth: 720 }}>
                            Retail, restaurant, pharmacy, water supply or distribution — pick the
                            modules you need, per shop. Multi-tenant, FBR-ready, offline-friendly.
                        </p>
                        <div className="d-flex gap-2 justify-content-center flex-wrap">
                            <Link to="/register-tenant" className="btn btn-warning btn-lg fw-semibold">
                                <FontAwesomeIcon icon={faBoltLightning} className="me-2" />
                                Start Free Trial
                            </Link>
                            <Button
                                size="lg"
                                variant="outline-light"
                                onClick={() => setShowDemo(true)}
                            >
                                Request Demo
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Business types */}
            <div className="container py-5">
                <h2 className="text-center fw-bold mb-4">Built for every business type</h2>
                <Row>
                    {BUSINESS_TYPES.map((t) => (
                        <Col md={4} sm={6} key={t.label} className="mb-3">
                            <Card className="shadow-sm h-100 border-0">
                                <Card.Body className="text-center">
                                    <div
                                        className="d-inline-flex align-items-center justify-content-center bg-light-primary rounded-circle mb-3"
                                        style={{ width: 64, height: 64 }}
                                    >
                                        <FontAwesomeIcon
                                            icon={t.icon}
                                            className="fs-2 text-primary"
                                        />
                                    </div>
                                    <h5 className="fw-bold">{t.label}</h5>
                                    <p className="text-muted mb-0 small">{t.text}</p>
                                </Card.Body>
                            </Card>
                        </Col>
                    ))}
                </Row>
            </div>

            {/* Pricing */}
            <div className="container py-5">
                <div className="text-center mb-4">
                    <h2 className="fw-bold mb-2">Simple, transparent pricing</h2>
                    <p className="text-muted">
                        Pay only for what you need. Upgrade, downgrade or add shops any time.
                    </p>
                    <div className="d-inline-flex bg-white rounded-pill shadow-sm p-1 mt-2">
                        <button
                            className={`btn ${
                                cycle === "monthly" ? "btn-primary" : "btn-light"
                            } rounded-pill px-4`}
                            onClick={() => setCycle("monthly")}
                        >
                            Monthly
                        </button>
                        <button
                            className={`btn ${
                                cycle === "yearly" ? "btn-primary" : "btn-light"
                            } rounded-pill px-4`}
                            onClick={() => setCycle("yearly")}
                        >
                            Yearly{" "}
                            <span className="badge bg-warning text-dark ms-1">save</span>
                        </button>
                    </div>
                </div>

                <Row>
                    {visiblePlans.length === 0 ? (
                        <Col>
                            <p className="text-center text-muted">
                                Plans are being configured. Please check back soon or{" "}
                                <button
                                    className="btn btn-link p-0"
                                    onClick={() => setShowDemo(true)}
                                >
                                    contact us
                                </button>
                                .
                            </p>
                        </Col>
                    ) : (
                        visiblePlans.map((plan, idx) => {
                            const features = plan.features || [];
                            const isHighlighted = idx === Math.floor(visiblePlans.length / 2);
                            return (
                                <Col md={Math.max(3, Math.floor(12 / visiblePlans.length))} key={plan.id} className="mb-3">
                                    <Card
                                        className={`h-100 shadow-sm ${
                                            isHighlighted
                                                ? "border-primary border-2"
                                                : "border-0"
                                        }`}
                                    >
                                        {isHighlighted && (
                                            <div
                                                className="bg-primary text-white text-center fw-semibold py-1"
                                                style={{ fontSize: 12 }}
                                            >
                                                Most popular
                                            </div>
                                        )}
                                        <Card.Body>
                                            <h5 className="fw-bold">{plan.name}</h5>
                                            <div className="my-3">
                                                <span className="fs-1 fw-bold">
                                                    {Number(plan.price) === 0
                                                        ? "Free"
                                                        : numberFmt(plan.price)}
                                                </span>
                                                {Number(plan.price) !== 0 && (
                                                    <span className="text-muted">
                                                        {" "}/ {plan.billing_cycle}
                                                    </span>
                                                )}
                                            </div>
                                            {plan.trial_days > 0 && (
                                                <p className="text-success small mb-2">
                                                    {plan.trial_days}-day free trial
                                                </p>
                                            )}
                                            {plan.description && (
                                                <p className="text-muted small">
                                                    {plan.description}
                                                </p>
                                            )}

                                            <ul className="list-unstyled mt-3">
                                                <li className="py-1">
                                                    <FontAwesomeIcon
                                                        icon={faCheck}
                                                        className="text-success me-2"
                                                    />
                                                    Up to{" "}
                                                    <strong>{plan.max_shops || "∞"}</strong>{" "}
                                                    shops
                                                </li>
                                                <li className="py-1">
                                                    <FontAwesomeIcon
                                                        icon={faCheck}
                                                        className="text-success me-2"
                                                    />
                                                    Up to{" "}
                                                    <strong>{plan.max_users || "∞"}</strong>{" "}
                                                    users
                                                </li>
                                                <li className="py-1">
                                                    <FontAwesomeIcon
                                                        icon={faCheck}
                                                        className="text-success me-2"
                                                    />
                                                    Up to{" "}
                                                    <strong>{plan.max_products || "∞"}</strong>{" "}
                                                    products
                                                </li>
                                                {Object.entries(FEATURE_LABELS).map(([k, label]) => {
                                                    const on = features.includes(k);
                                                    return (
                                                        <li key={k} className="py-1">
                                                            <FontAwesomeIcon
                                                                icon={on ? faCheck : faTimes}
                                                                className={`me-2 ${
                                                                    on
                                                                        ? "text-success"
                                                                        : "text-muted opacity-50"
                                                                }`}
                                                            />
                                                            <span
                                                                className={
                                                                    on
                                                                        ? ""
                                                                        : "text-muted text-decoration-line-through"
                                                                }
                                                            >
                                                                {label}
                                                            </span>
                                                        </li>
                                                    );
                                                })}
                                            </ul>

                                            <Link
                                                to="/register-tenant"
                                                className={`btn ${
                                                    isHighlighted
                                                        ? "btn-primary"
                                                        : "btn-outline-primary"
                                                } w-100 mt-3`}
                                            >
                                                Choose {plan.name}
                                            </Link>
                                        </Card.Body>
                                    </Card>
                                </Col>
                            );
                        })
                    )}
                </Row>
            </div>

            {/* Bottom CTA */}
            <div className="container py-5">
                <Card className="bg-dark text-white border-0 shadow">
                    <Card.Body className="p-5 text-center">
                        <h3 className="fw-bold mb-2">
                            Need a custom plan or enterprise rollout?
                        </h3>
                        <p className="opacity-75 mb-4">
                            We work with chains, franchises and distributors with multiple
                            branches.
                        </p>
                        <Button
                            variant="warning"
                            size="lg"
                            onClick={() => setShowDemo(true)}
                        >
                            Talk to Sales
                        </Button>
                    </Card.Body>
                </Card>
            </div>

            <DemoModal show={showDemo} onHide={() => setShowDemo(false)} />
        </div>
    );
};

export default Pricing;
