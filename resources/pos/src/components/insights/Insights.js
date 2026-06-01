import React, { useEffect, useState } from "react";
import { Card, Row, Col, Table, Tabs, Tab } from "react-bootstrap";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";

const numberFmt = (n) =>
    Number(n || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const urgencyBadge = (u) => {
    const m = {
        critical: "bg-danger",
        high: "bg-warning text-dark",
        medium: "bg-info text-dark",
        low: "bg-light-secondary",
    };
    return `badge text-capitalize ${m[u] || "bg-light-primary"}`;
};

const MiniBar = ({ value, max }) => {
    const pct = max > 0 ? Math.min(100, (value / max) * 100) : 0;
    return (
        <div className="progress" style={{ height: 6, minWidth: 80 }}>
            <div
                className="progress-bar bg-primary"
                style={{ width: pct + "%" }}
            />
        </div>
    );
};

const SalesForecastTab = () => {
    const [data, setData] = useState(null);
    const [historyDays, setHistoryDays] = useState(30);
    const [forecastDays, setForecastDays] = useState(14);

    useEffect(() => {
        apiConfig
            .get("insights/sales-forecast", {
                params: { history_days: historyDays, forecast_days: forecastDays },
            })
            .then((res) => setData(res.data?.data))
            .catch(() => setData(null));
    }, [historyDays, forecastDays]);

    if (!data)
        return <p className="text-muted">Loading forecast…</p>;

    const series = [
        ...(data.history || []).map((r) => ({
            date: r.date,
            actual: r.sales,
            forecast: null,
        })),
        ...(data.forecast || []).map((r) => ({
            date: r.date,
            actual: null,
            forecast: r.forecast,
        })),
    ];

    const maxVal = Math.max(
        1,
        ...series.map((s) => Math.max(s.actual || 0, s.forecast || 0))
    );

    return (
        <div>
            <div className="d-flex gap-3 mb-3 align-items-end">
                <div>
                    <label className="form-label small mb-1">History (days)</label>
                    <input
                        type="number"
                        className="form-control"
                        style={{ width: 110 }}
                        value={historyDays}
                        onChange={(e) => setHistoryDays(e.target.value)}
                    />
                </div>
                <div>
                    <label className="form-label small mb-1">Forecast (days)</label>
                    <input
                        type="number"
                        className="form-control"
                        style={{ width: 110 }}
                        value={forecastDays}
                        onChange={(e) => setForecastDays(e.target.value)}
                    />
                </div>
            </div>

            <Row className="mb-3">
                <Col md={3}>
                    <Card className="shadow-sm">
                        <Card.Body>
                            <div className="text-muted small">Avg daily sales</div>
                            <div className="fs-3 fw-bold">
                                {numberFmt(data.metrics?.avg_daily_sales)}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="shadow-sm">
                        <Card.Body>
                            <div className="text-muted small">Weighted recent avg</div>
                            <div className="fs-3 fw-bold">
                                {numberFmt(data.metrics?.weighted_avg_recent)}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="shadow-sm">
                        <Card.Body>
                            <div className="text-muted small">Trend slope</div>
                            <div
                                className={`fs-3 fw-bold ${
                                    Number(data.metrics?.trend_slope) >= 0
                                        ? "text-success"
                                        : "text-danger"
                                }`}
                            >
                                {data.metrics?.trend_slope >= 0 ? "↗" : "↘"}{" "}
                                {numberFmt(Math.abs(data.metrics?.trend_slope || 0))}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={3}>
                    <Card className="shadow-sm">
                        <Card.Body>
                            <div className="text-muted small">
                                Forecast total ({forecastDays}d)
                            </div>
                            <div className="fs-3 fw-bold text-primary">
                                {numberFmt(data.metrics?.forecast_total)}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            <Card className="shadow-sm">
                <Card.Body>
                    <Table responsive bordered size="sm" className="mb-0">
                        <thead className="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th className="text-end">Amount</th>
                                <th style={{ width: "30%" }}>Bar</th>
                            </tr>
                        </thead>
                        <tbody>
                            {series.map((r) => (
                                <tr key={r.date}>
                                    <td>{r.date}</td>
                                    <td>
                                        {r.actual !== null ? (
                                            <span className="badge bg-light-primary">
                                                Actual
                                            </span>
                                        ) : (
                                            <span className="badge bg-light-warning">
                                                Forecast
                                            </span>
                                        )}
                                    </td>
                                    <td className="text-end">
                                        {numberFmt(
                                            r.actual !== null ? r.actual : r.forecast
                                        )}
                                    </td>
                                    <td>
                                        <MiniBar
                                            value={
                                                r.actual !== null
                                                    ? r.actual
                                                    : r.forecast
                                            }
                                            max={maxVal}
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </Table>
                </Card.Body>
            </Card>
        </div>
    );
};

const ReorderTab = () => {
    const [data, setData] = useState(null);
    const [days, setDays] = useState(30);
    const [cover, setCover] = useState(14);

    useEffect(() => {
        apiConfig
            .get("insights/reorder-suggestions", {
                params: { days, cover_days: cover },
            })
            .then((res) => setData(res.data?.data))
            .catch(() => setData(null));
    }, [days, cover]);

    return (
        <div>
            <div className="d-flex gap-3 mb-3 align-items-end">
                <div>
                    <label className="form-label small mb-1">Lookback (days)</label>
                    <input
                        type="number"
                        className="form-control"
                        style={{ width: 110 }}
                        value={days}
                        onChange={(e) => setDays(e.target.value)}
                    />
                </div>
                <div>
                    <label className="form-label small mb-1">
                        Target cover (days)
                    </label>
                    <input
                        type="number"
                        className="form-control"
                        style={{ width: 110 }}
                        value={cover}
                        onChange={(e) => setCover(e.target.value)}
                    />
                </div>
            </div>

            <Card className="shadow-sm">
                <Card.Body>
                    <Table responsive bordered size="sm" hover className="mb-0">
                        <thead className="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Product</th>
                                <th className="text-end">Velocity/day</th>
                                <th className="text-end">Current Stock</th>
                                <th className="text-end">Days of Cover</th>
                                <th className="text-end">Suggested Reorder</th>
                                <th>Urgency</th>
                            </tr>
                        </thead>
                        <tbody>
                            {!data || data.rows?.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan="7"
                                        className="text-muted text-center py-3"
                                    >
                                        No reorder suggestions
                                    </td>
                                </tr>
                            ) : (
                                data.rows.map((r) => (
                                    <tr key={r.product_id}>
                                        <td>{r.code}</td>
                                        <td>{r.name}</td>
                                        <td className="text-end">
                                            {numberFmt(r.velocity_per_day)}
                                        </td>
                                        <td className="text-end">
                                            {numberFmt(r.current_stock)}
                                        </td>
                                        <td className="text-end">
                                            {r.days_of_cover ?? "—"}
                                        </td>
                                        <td className="text-end fw-semibold">
                                            {numberFmt(r.suggested_reorder_qty)}
                                        </td>
                                        <td>
                                            <span className={urgencyBadge(r.urgency)}>
                                                {r.urgency}
                                            </span>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </Table>
                </Card.Body>
            </Card>
        </div>
    );
};

const CustomerTrendsTab = () => {
    const [data, setData] = useState(null);
    const [days, setDays] = useState(90);

    useEffect(() => {
        apiConfig
            .get("insights/customer-trends", { params: { days } })
            .then((res) => setData(res.data?.data))
            .catch(() => setData(null));
    }, [days]);

    return (
        <div>
            <div className="d-flex gap-3 mb-3 align-items-end">
                <div>
                    <label className="form-label small mb-1">Window (days)</label>
                    <input
                        type="number"
                        className="form-control"
                        style={{ width: 110 }}
                        value={days}
                        onChange={(e) => setDays(e.target.value)}
                    />
                </div>
            </div>
            <Card className="shadow-sm">
                <Card.Body>
                    <Table responsive bordered size="sm" hover className="mb-0">
                        <thead className="table-light">
                            <tr>
                                <th>Customer</th>
                                <th className="text-end">Orders</th>
                                <th className="text-end">Revenue</th>
                                <th className="text-end">Avg Ticket</th>
                            </tr>
                        </thead>
                        <tbody>
                            {!data || data.rows?.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan="4"
                                        className="text-muted text-center py-3"
                                    >
                                        No sales in this period
                                    </td>
                                </tr>
                            ) : (
                                data.rows.map((r, idx) => (
                                    <tr key={idx}>
                                        <td>{r.name}</td>
                                        <td className="text-end">{r.orders}</td>
                                        <td className="text-end">
                                            {numberFmt(r.revenue)}
                                        </td>
                                        <td className="text-end">
                                            {numberFmt(r.avg_ticket)}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </Table>
                </Card.Body>
            </Card>
        </div>
    );
};

const Insights = () => {
    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="AI Insights" />
            <div className="mb-3">
                <h4 className="mb-0">AI Insights</h4>
                <small className="text-muted">
                    Sales forecast, reorder suggestions and customer trends
                </small>
            </div>
            <Tabs defaultActiveKey="forecast" className="mb-3">
                <Tab eventKey="forecast" title="Sales Forecast">
                    <SalesForecastTab />
                </Tab>
                <Tab eventKey="reorder" title="Reorder Suggestions">
                    <ReorderTab />
                </Tab>
                <Tab eventKey="customers" title="Customer Trends">
                    <CustomerTrendsTab />
                </Tab>
            </Tabs>
        </MasterLayout>
    );
};

export default Insights;
