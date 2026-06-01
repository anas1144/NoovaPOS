import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Modal, Button } from "react-bootstrap-v5";
import { Card, Row, Col } from "react-bootstrap";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import {
    fetchTables,
    saveTable,
    deleteTable,
    changeTableState,
    fetchHalls,
} from "../../store/action/restaurantAction";

const STATES = [
    { value: "free", label: "Free", badge: "bg-success" },
    { value: "occupied", label: "Occupied", badge: "bg-danger" },
    { value: "reserved", label: "Reserved", badge: "bg-warning text-dark" },
    { value: "billed", label: "Billed", badge: "bg-info" },
];

const TableForm = ({ show, data, halls, onHide }) => {
    const dispatch = useDispatch();
    const [form, setForm] = useState({
        name: "",
        code: "",
        hall_id: "",
        seats: 2,
        state: "free",
    });

    useEffect(() => {
        setForm({
            name: data?.name || "",
            code: data?.code || "",
            hall_id: data?.hall_id || "",
            seats: data?.seats || 2,
            state: data?.state || "free",
        });
    }, [data, show]);

    const setF = (k) => (e) => setForm((s) => ({ ...s, [k]: e.target.value }));

    return (
        <Modal show={show} onHide={onHide}>
            <Modal.Header closeButton>
                <Modal.Title>{data?.id ? "Edit Table" : "Create Table"}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-12 mb-3">
                        <label className="form-label">Name</label>
                        <input
                            className="form-control"
                            value={form.name}
                            onChange={setF("name")}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Hall</label>
                        <select
                            className="form-control"
                            value={form.hall_id || ""}
                            onChange={setF("hall_id")}
                        >
                            <option value="">-- None --</option>
                            {halls.map((h) => (
                                <option key={h.id} value={h.id}>
                                    {h.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="col-md-3 mb-3">
                        <label className="form-label">Seats</label>
                        <input
                            type="number"
                            className="form-control"
                            value={form.seats}
                            onChange={setF("seats")}
                        />
                    </div>
                    <div className="col-md-3 mb-3">
                        <label className="form-label">Code</label>
                        <input
                            className="form-control"
                            value={form.code}
                            onChange={setF("code")}
                        />
                    </div>
                </div>
            </Modal.Body>
            <Modal.Footer>
                <Button onClick={() => dispatch(saveTable(form, data?.id, onHide))}>
                    Save
                </Button>
                <Button variant="secondary" onClick={onHide}>
                    Cancel
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

const Tables = () => {
    const dispatch = useDispatch();
    const tables = useSelector((s) => s.restaurant?.tables || []);
    const halls = useSelector((s) => s.restaurant?.halls || []);
    const [show, setShow] = useState(false);
    const [editData, setEditData] = useState(null);
    const [filterHall, setFilterHall] = useState("");
    const [filterState, setFilterState] = useState("");

    useEffect(() => {
        dispatch(fetchHalls());
        dispatch(fetchTables());
    }, []);

    const filtered = (tables || []).filter(
        (t) =>
            (!filterHall || String(t.hall_id) === String(filterHall)) &&
            (!filterState || t.state === filterState)
    );

    const handleCycle = (table) => {
        const order = ["free", "occupied", "reserved", "billed"];
        const next = order[(order.indexOf(table.state || "free") + 1) % order.length];
        dispatch(changeTableState(table.id, next));
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Tables" />
            <div className="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div className="d-flex gap-2">
                    <select
                        className="form-control"
                        value={filterHall}
                        onChange={(e) => setFilterHall(e.target.value)}
                    >
                        <option value="">All halls</option>
                        {halls.map((h) => (
                            <option key={h.id} value={h.id}>
                                {h.name}
                            </option>
                        ))}
                    </select>
                    <select
                        className="form-control"
                        value={filterState}
                        onChange={(e) => setFilterState(e.target.value)}
                    >
                        <option value="">All states</option>
                        {STATES.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>
                </div>
                <Button
                    onClick={() => {
                        setEditData(null);
                        setShow(true);
                    }}
                >
                    + New Table
                </Button>
            </div>
            <Row>
                {filtered.length === 0 ? (
                    <Col>
                        <p className="text-muted text-center mt-4">No tables yet</p>
                    </Col>
                ) : (
                    filtered.map((t) => {
                        const stateConf =
                            STATES.find((s) => s.value === t.state) || STATES[0];
                        return (
                            <Col
                                xs={6}
                                md={3}
                                lg={2}
                                key={t.id}
                                className="mb-3"
                            >
                                <Card className="text-center shadow-sm h-100 table-card">
                                    <Card.Body>
                                        <div className="fw-bold fs-5 mb-1">
                                            {t.name}
                                        </div>
                                        <div className="text-muted small mb-2">
                                            {t.hall?.name || "—"} · {t.seats} seats
                                        </div>
                                        <span
                                            className={`badge ${stateConf.badge} px-3 py-2 mb-2 cursor-pointer`}
                                            onClick={() => handleCycle(t)}
                                        >
                                            {stateConf.label}
                                        </span>
                                        <div className="d-flex gap-2 justify-content-center mt-2">
                                            <button
                                                className="btn btn-sm btn-outline-primary"
                                                onClick={() => {
                                                    setEditData(t);
                                                    setShow(true);
                                                }}
                                            >
                                                Edit
                                            </button>
                                            <button
                                                className="btn btn-sm btn-outline-danger"
                                                onClick={() =>
                                                    dispatch(deleteTable(t.id))
                                                }
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </Card.Body>
                                </Card>
                            </Col>
                        );
                    })
                )}
            </Row>
            <TableForm
                show={show}
                data={editData}
                halls={halls}
                onHide={() => setShow(false)}
            />
        </MasterLayout>
    );
};

export default Tables;
