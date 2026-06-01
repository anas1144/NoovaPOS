import React, { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Card, Row, Col } from "react-bootstrap";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import { fetchKots, updateKotStatus } from "../../store/action/restaurantAction";
import moment from "moment";

const COLUMNS = [
    { key: "open", title: "Open", next: "sent", btn: "Send to Kitchen" },
    { key: "sent", title: "Sent", next: "in_progress", btn: "Start cooking" },
    { key: "in_progress", title: "Cooking", next: "ready", btn: "Mark ready" },
    { key: "ready", title: "Ready", next: "served", btn: "Mark served" },
    { key: "served", title: "Served", next: null, btn: null },
];

const KotBoard = () => {
    const dispatch = useDispatch();
    const kots = useSelector((s) => s.restaurant?.kots || []);

    useEffect(() => {
        dispatch(fetchKots());
        const t = setInterval(() => dispatch(fetchKots()), 15000);
        return () => clearInterval(t);
    }, []);

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="KOT (Kitchen Display)" />
            <div className="d-flex justify-content-between mb-3">
                <h4 className="mb-0">Kitchen Order Tickets</h4>
                <small className="text-muted">Auto-refreshing every 15s</small>
            </div>
            <Row>
                {COLUMNS.map((col) => {
                    const items = (kots || []).filter((k) => k.status === col.key);
                    return (
                        <Col md={2} key={col.key} className="mb-3">
                            <div className="bg-light-primary rounded p-2 mb-2 text-center fw-semibold">
                                {col.title}{" "}
                                <span className="badge bg-primary ms-1">
                                    {items.length}
                                </span>
                            </div>
                            {items.length === 0 ? (
                                <p className="text-muted text-center small">—</p>
                            ) : (
                                items.map((kot) => (
                                    <Card key={kot.id} className="mb-2 shadow-sm">
                                        <Card.Body className="p-2">
                                            <div className="d-flex justify-content-between align-items-center mb-1">
                                                <strong className="small">
                                                    {kot.ticket_no || `#${kot.id}`}
                                                </strong>
                                                <small className="text-muted">
                                                    {kot.created_at
                                                        ? moment(kot.created_at).fromNow(true)
                                                        : ""}
                                                </small>
                                            </div>
                                            <div className="small text-muted mb-1">
                                                {kot.table?.name
                                                    ? `Table: ${kot.table.name}`
                                                    : "Takeaway/Delivery"}
                                            </div>
                                            <ul className="list-unstyled mb-2 small">
                                                {(kot.items || []).map((i) => (
                                                    <li key={i.id}>
                                                        • {i.product_name} ×{" "}
                                                        {Number(i.quantity)}
                                                        {i.modifier && (
                                                            <small className="text-muted d-block ms-3">
                                                                {i.modifier}
                                                            </small>
                                                        )}
                                                    </li>
                                                ))}
                                            </ul>
                                            {col.next && (
                                                <button
                                                    className="btn btn-sm btn-outline-primary w-100"
                                                    onClick={() =>
                                                        dispatch(
                                                            updateKotStatus(
                                                                kot.id,
                                                                col.next
                                                            )
                                                        )
                                                    }
                                                >
                                                    {col.btn}
                                                </button>
                                            )}
                                        </Card.Body>
                                    </Card>
                                ))
                            )}
                        </Col>
                    );
                })}
            </Row>
        </MasterLayout>
    );
};

export default KotBoard;
