import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Card, Row, Col, Table } from "react-bootstrap";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import moment from "moment";
import { fetchProfitAndLoss } from "../../store/action/accountingAction";

const ProfitLoss = () => {
    const dispatch = useDispatch();
    const [data, setData] = useState({
        revenue: [],
        expense: [],
        totals: { revenue: 0, expense: 0, net_profit: 0 },
    });
    const [start, setStart] = useState(moment().startOf("month").format("YYYY-MM-DD"));
    const [end, setEnd] = useState(moment().format("YYYY-MM-DD"));

    const load = async () => {
        const result = await dispatch(
            fetchProfitAndLoss({ start_date: start, end_date: end })
        );
        if (result) setData(result);
    };

    useEffect(() => {
        load();
    }, [start, end]);

    const numberFmt = (n) => Number(n || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Profit & Loss" />
            <div className="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h4 className="mb-0">Profit & Loss Statement</h4>
                <div className="d-flex align-items-center gap-2">
                    <label className="form-label mb-0">From</label>
                    <input
                        type="date"
                        className="form-control"
                        style={{ width: 160 }}
                        value={start}
                        onChange={(e) => setStart(e.target.value)}
                    />
                    <label className="form-label mb-0">To</label>
                    <input
                        type="date"
                        className="form-control"
                        style={{ width: 160 }}
                        value={end}
                        onChange={(e) => setEnd(e.target.value)}
                    />
                </div>
            </div>

            <Row className="mb-3">
                <Col md={4}>
                    <Card className="shadow-sm">
                        <Card.Body>
                            <div className="text-muted small">Revenue</div>
                            <div className="fs-3 fw-bold text-success">
                                {numberFmt(data.totals?.revenue)}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card className="shadow-sm">
                        <Card.Body>
                            <div className="text-muted small">Expenses</div>
                            <div className="fs-3 fw-bold text-danger">
                                {numberFmt(data.totals?.expense)}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card className="shadow-sm">
                        <Card.Body>
                            <div className="text-muted small">Net Profit</div>
                            <div
                                className={`fs-3 fw-bold ${
                                    Number(data.totals?.net_profit) >= 0
                                        ? "text-success"
                                        : "text-danger"
                                }`}
                            >
                                {numberFmt(data.totals?.net_profit)}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            <Row>
                <Col md={6}>
                    <Card className="shadow-sm mb-3">
                        <Card.Header className="bg-success text-white fw-semibold">
                            Revenue
                        </Card.Header>
                        <Card.Body>
                            <Table size="sm" responsive className="mb-0">
                                <tbody>
                                    {(data.revenue || []).length === 0 ? (
                                        <tr>
                                            <td className="text-muted text-center py-2">
                                                No revenue recorded
                                            </td>
                                        </tr>
                                    ) : (
                                        (data.revenue || []).map((r) => (
                                            <tr key={r.id}>
                                                <td>
                                                    <small className="text-muted">
                                                        {r.code}
                                                    </small>{" "}
                                                    {r.name}
                                                </td>
                                                <td className="text-end">
                                                    {numberFmt(r.amount)}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                                <tfoot>
                                    <tr className="fw-bold table-light">
                                        <td>Total Revenue</td>
                                        <td className="text-end">
                                            {numberFmt(data.totals?.revenue)}
                                        </td>
                                    </tr>
                                </tfoot>
                            </Table>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={6}>
                    <Card className="shadow-sm mb-3">
                        <Card.Header className="bg-danger text-white fw-semibold">
                            Expenses
                        </Card.Header>
                        <Card.Body>
                            <Table size="sm" responsive className="mb-0">
                                <tbody>
                                    {(data.expense || []).length === 0 ? (
                                        <tr>
                                            <td className="text-muted text-center py-2">
                                                No expenses recorded
                                            </td>
                                        </tr>
                                    ) : (
                                        (data.expense || []).map((r) => (
                                            <tr key={r.id}>
                                                <td>
                                                    <small className="text-muted">
                                                        {r.code}
                                                    </small>{" "}
                                                    {r.name}
                                                </td>
                                                <td className="text-end">
                                                    {numberFmt(r.amount)}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                                <tfoot>
                                    <tr className="fw-bold table-light">
                                        <td>Total Expenses</td>
                                        <td className="text-end">
                                            {numberFmt(data.totals?.expense)}
                                        </td>
                                    </tr>
                                </tfoot>
                            </Table>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        </MasterLayout>
    );
};

export default ProfitLoss;
