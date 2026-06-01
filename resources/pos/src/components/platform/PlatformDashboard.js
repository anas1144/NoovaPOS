import React, { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Row, Col, Card } from "react-bootstrap";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
    faBuilding,
    faUserGroup,
    faBoxes,
    faMoneyBillWave,
    faDatabase,
    faCheckCircle,
    faBan,
} from "@fortawesome/free-solid-svg-icons";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import { fetchPlatformDashboard } from "../../store/action/platformAction";

const Stat = ({ icon, label, value, color }) => (
    <Col md={3} sm={6} className="mb-3">
        <Card className="shadow-sm h-100 platform-stat-card">
            <Card.Body className="d-flex align-items-center gap-3">
                <div
                    className={`d-flex justify-content-center align-items-center rounded ${color || "bg-light-primary"}`}
                    style={{ width: 56, height: 56 }}
                >
                    <FontAwesomeIcon icon={icon} className="fs-2 text-white" />
                </div>
                <div>
                    <div className="text-muted small">{label}</div>
                    <div className="fs-3 fw-bold">{value}</div>
                </div>
            </Card.Body>
        </Card>
    </Col>
);

const PlatformDashboard = () => {
    const dispatch = useDispatch();
    const dashboard = useSelector((state) => state.platform?.dashboard);

    useEffect(() => {
        dispatch(fetchPlatformDashboard());
    }, []);

    const d = dashboard || {
        tenants: { total: 0, active_or_trialing: 0, suspended_or_inactive: 0 },
        subscriptions: { by_status: {}, by_plan: {} },
        usage: {
            users: 0,
            products: 0,
            customers: 0,
            sales: 0,
            sales_total: 0,
        },
        backups: { queued: 0, running: 0, failed: 0 },
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Platform Dashboard" />
            <div className="mb-4">
                <h4 className="mb-1">Platform Overview</h4>
                <p className="text-muted mb-0">
                    SaaS health, tenant activity & system usage at a glance.
                </p>
            </div>
            <Row>
                <Stat
                    icon={faBuilding}
                    label="Total Tenants"
                    value={d.tenants.total}
                    color="bg-primary"
                />
                <Stat
                    icon={faCheckCircle}
                    label="Active / Trialing"
                    value={d.tenants.active_or_trialing}
                    color="bg-success"
                />
                <Stat
                    icon={faBan}
                    label="Suspended / Inactive"
                    value={d.tenants.suspended_or_inactive}
                    color="bg-danger"
                />
                <Stat
                    icon={faMoneyBillWave}
                    label="Total Sales Volume"
                    value={Number(d.usage.sales_total || 0).toLocaleString()}
                    color="bg-info"
                />
            </Row>

            <Row className="mt-2">
                <Stat
                    icon={faUserGroup}
                    label="Users (all tenants)"
                    value={d.usage.users}
                    color="bg-warning"
                />
                <Stat
                    icon={faBoxes}
                    label="Products"
                    value={d.usage.products}
                    color="bg-secondary"
                />
                <Stat
                    icon={faUserGroup}
                    label="Customers"
                    value={d.usage.customers}
                    color="bg-primary"
                />
                <Stat
                    icon={faDatabase}
                    label="Sales"
                    value={d.usage.sales}
                    color="bg-success"
                />
            </Row>

            <Row className="mt-4">
                <Col md={6} className="mb-3">
                    <Card className="shadow-sm h-100">
                        <Card.Header className="bg-white fw-semibold">
                            Subscriptions by Status
                        </Card.Header>
                        <Card.Body>
                            {Object.keys(d.subscriptions.by_status || {}).length === 0 ? (
                                <p className="text-muted mb-0">No subscriptions yet</p>
                            ) : (
                                <ul className="list-unstyled mb-0">
                                    {Object.entries(d.subscriptions.by_status).map(
                                        ([s, c]) => (
                                            <li
                                                key={s}
                                                className="d-flex justify-content-between py-1"
                                            >
                                                <span className="text-capitalize">{s}</span>
                                                <span className="badge bg-light-primary">{c}</span>
                                            </li>
                                        )
                                    )}
                                </ul>
                            )}
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={6} className="mb-3">
                    <Card className="shadow-sm h-100">
                        <Card.Header className="bg-white fw-semibold">
                            Subscriptions by Plan
                        </Card.Header>
                        <Card.Body>
                            {Object.keys(d.subscriptions.by_plan || {}).length === 0 ? (
                                <p className="text-muted mb-0">No active plans</p>
                            ) : (
                                <ul className="list-unstyled mb-0">
                                    {Object.entries(d.subscriptions.by_plan).map(
                                        ([plan, c]) => (
                                            <li
                                                key={plan}
                                                className="d-flex justify-content-between py-1"
                                            >
                                                <span>{plan}</span>
                                                <span className="badge bg-light-success">{c}</span>
                                            </li>
                                        )
                                    )}
                                </ul>
                            )}
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            <Row className="mt-2">
                <Col md={12} className="mb-3">
                    <Card className="shadow-sm">
                        <Card.Header className="bg-white fw-semibold">
                            Tenant Backups
                        </Card.Header>
                        <Card.Body>
                            <Row className="text-center">
                                <Col>
                                    <div className="text-muted small">Queued</div>
                                    <div className="fs-3 fw-bold text-warning">
                                        {d.backups.queued}
                                    </div>
                                </Col>
                                <Col>
                                    <div className="text-muted small">Running</div>
                                    <div className="fs-3 fw-bold text-info">
                                        {d.backups.running}
                                    </div>
                                </Col>
                                <Col>
                                    <div className="text-muted small">Failed</div>
                                    <div className="fs-3 fw-bold text-danger">
                                        {d.backups.failed}
                                    </div>
                                </Col>
                                <Col>
                                    <div className="text-muted small">
                                        Last Completed
                                    </div>
                                    <div className="fs-6 fw-semibold">
                                        {d.backups.latest_completed_at
                                            ? new Date(
                                                  d.backups.latest_completed_at
                                              ).toLocaleString()
                                            : "—"}
                                    </div>
                                </Col>
                            </Row>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        </MasterLayout>
    );
};

export default PlatformDashboard;
