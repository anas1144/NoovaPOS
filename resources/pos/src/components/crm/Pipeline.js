import React, { useEffect, useState } from "react";
import { Card, Row, Col, Modal, Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import moment from "moment";

const STAGES = [
    { key: "new", title: "New", color: "primary" },
    { key: "contacted", title: "Contacted", color: "info" },
    { key: "qualified", title: "Qualified", color: "warning" },
    { key: "proposal", title: "Proposal", color: "primary" },
    { key: "won", title: "Won", color: "success" },
    { key: "lost", title: "Lost", color: "danger" },
];

const SOURCES = ["walk_in", "website", "referral", "ad", "demo_request", "manual"];

const LeadForm = ({ show, data, onHide, onSaved }) => {
    const [f, setF] = useState({});

    useEffect(() => {
        setF({
            name: data?.name || "",
            email: data?.email || "",
            phone: data?.phone || "",
            company: data?.company || "",
            source: data?.source || "manual",
            stage: data?.stage || "new",
            estimated_value: data?.estimated_value || 0,
            probability: data?.probability || 0,
            expected_close_date: data?.expected_close_date || "",
            notes: data?.notes || "",
        });
    }, [data, show]);

    const set = (k) => (e) => setF((s) => ({ ...s, [k]: e.target.value }));

    const submit = async () => {
        try {
            if (data?.id) {
                await apiConfig.patch("crm/leads/" + data.id, f);
            } else {
                await apiConfig.post("crm/leads", f);
            }
            onSaved && onSaved();
            onHide();
        } catch (err) {
            console.error(err);
        }
    };

    return (
        <Modal show={show} onHide={onHide} size="lg">
            <Modal.Header closeButton>
                <Modal.Title>{data?.id ? "Edit Lead" : "Create Lead"}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Name</label>
                        <input
                            className="form-control"
                            value={f.name || ""}
                            onChange={set("name")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Company</label>
                        <input
                            className="form-control"
                            value={f.company || ""}
                            onChange={set("company")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Email</label>
                        <input
                            type="email"
                            className="form-control"
                            value={f.email || ""}
                            onChange={set("email")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Phone</label>
                        <input
                            className="form-control"
                            value={f.phone || ""}
                            onChange={set("phone")}
                        />
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Source</label>
                        <select
                            className="form-control"
                            value={f.source || "manual"}
                            onChange={set("source")}
                        >
                            {SOURCES.map((s) => (
                                <option key={s} value={s}>
                                    {s.replace("_", " ")}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Stage</label>
                        <select
                            className="form-control"
                            value={f.stage || "new"}
                            onChange={set("stage")}
                        >
                            {STAGES.map((s) => (
                                <option key={s.key} value={s.key}>
                                    {s.title}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Expected Close</label>
                        <input
                            type="date"
                            className="form-control"
                            value={f.expected_close_date || ""}
                            onChange={set("expected_close_date")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Estimated Value</label>
                        <input
                            type="number"
                            step="0.01"
                            className="form-control"
                            value={f.estimated_value || 0}
                            onChange={set("estimated_value")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Probability (%)</label>
                        <input
                            type="number"
                            min="0"
                            max="100"
                            className="form-control"
                            value={f.probability || 0}
                            onChange={set("probability")}
                        />
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-label">Notes</label>
                        <textarea
                            className="form-control"
                            rows="3"
                            value={f.notes || ""}
                            onChange={set("notes")}
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

const Pipeline = () => {
    const [leads, setLeads] = useState([]);
    const [stats, setStats] = useState(null);
    const [show, setShow] = useState(false);
    const [editData, setEditData] = useState(null);

    const load = async () => {
        const [l, s] = await Promise.all([
            apiConfig.get("crm/leads"),
            apiConfig.get("crm/pipeline-stats"),
        ]);
        setLeads(l.data?.data?.data || l.data?.data || []);
        setStats(s.data?.data);
    };

    useEffect(() => {
        load();
    }, []);

    const move = async (lead, stage) => {
        await apiConfig.patch("crm/leads/" + lead.id + "/stage", { stage });
        load();
    };

    const remove = async (lead) => {
        if (!window.confirm("Delete this lead?")) return;
        await apiConfig.delete("crm/leads/" + lead.id);
        load();
    };

    const numberFmt = (n) =>
        Number(n || 0).toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        });

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="CRM Pipeline" />
            <div className="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h4 className="mb-0">CRM Pipeline</h4>
                <Button
                    onClick={() => {
                        setEditData(null);
                        setShow(true);
                    }}
                >
                    + New Lead
                </Button>
            </div>

            {stats && (
                <Row className="mb-3">
                    <Col md={3}>
                        <Card className="shadow-sm">
                            <Card.Body>
                                <div className="text-muted small">Open Leads</div>
                                <div className="fs-3 fw-bold">
                                    {stats.totals?.open_leads}
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="shadow-sm">
                            <Card.Body>
                                <div className="text-muted small">
                                    Pipeline Value
                                </div>
                                <div className="fs-3 fw-bold text-primary">
                                    {numberFmt(stats.totals?.pipeline_value)}
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="shadow-sm">
                            <Card.Body>
                                <div className="text-muted small">Won</div>
                                <div className="fs-3 fw-bold text-success">
                                    {stats.totals?.won}
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col md={3}>
                        <Card className="shadow-sm">
                            <Card.Body>
                                <div className="text-muted small">Win rate</div>
                                <div className="fs-3 fw-bold">
                                    {stats.totals?.win_rate}%
                                </div>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            )}

            <Row>
                {STAGES.map((stage) => {
                    const items = (leads || []).filter((l) => l.stage === stage.key);
                    return (
                        <Col md={2} key={stage.key} className="mb-3">
                            <div
                                className={`bg-light-${stage.color} rounded p-2 mb-2 text-center fw-semibold`}
                            >
                                {stage.title}{" "}
                                <span className="badge bg-secondary ms-1">
                                    {items.length}
                                </span>
                            </div>
                            {items.length === 0 ? (
                                <p className="text-muted text-center small">—</p>
                            ) : (
                                items.map((lead) => (
                                    <Card key={lead.id} className="mb-2 shadow-sm">
                                        <Card.Body className="p-2">
                                            <div className="fw-semibold small">
                                                {lead.name}
                                            </div>
                                            {lead.company && (
                                                <div className="text-muted small">
                                                    {lead.company}
                                                </div>
                                            )}
                                            <div className="d-flex justify-content-between mt-1">
                                                <span className="badge bg-light-primary">
                                                    {numberFmt(lead.estimated_value)}
                                                </span>
                                                <small className="text-muted">
                                                    {lead.expected_close_date
                                                        ? moment(
                                                              lead.expected_close_date
                                                          ).format("MMM D")
                                                        : ""}
                                                </small>
                                            </div>
                                            <div className="mt-2 d-flex gap-1 flex-wrap">
                                                <select
                                                    className="form-control form-control-sm"
                                                    value={lead.stage}
                                                    onChange={(e) =>
                                                        move(lead, e.target.value)
                                                    }
                                                    style={{ fontSize: 11 }}
                                                >
                                                    {STAGES.map((s) => (
                                                        <option
                                                            key={s.key}
                                                            value={s.key}
                                                        >
                                                            → {s.title}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div className="mt-1 d-flex justify-content-end gap-1">
                                                <button
                                                    className="btn btn-link btn-sm p-0"
                                                    onClick={() => {
                                                        setEditData(lead);
                                                        setShow(true);
                                                    }}
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    className="btn btn-link btn-sm p-0 text-danger"
                                                    onClick={() => remove(lead)}
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </Card.Body>
                                    </Card>
                                ))
                            )}
                        </Col>
                    );
                })}
            </Row>
            <LeadForm
                show={show}
                data={editData}
                onHide={() => setShow(false)}
                onSaved={load}
            />
        </MasterLayout>
    );
};

export default Pipeline;
