import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import { Card, Row, Col, Table } from "react-bootstrap";
import { fetchTrialBalance } from "../../store/action/accountingAction";
import moment from "moment";

const typeBadge = (t) => {
    const m = {
        asset: "bg-light-success",
        liability: "bg-light-danger",
        equity: "bg-light-primary",
        revenue: "bg-light-info",
        expense: "bg-light-warning",
    };
    return `badge text-capitalize ${m[t] || "bg-light-secondary"}`;
};

const TrialBalance = () => {
    const dispatch = useDispatch();
    const tb = useSelector(
        (s) =>
            s.accounting?.trialBalance || { rows: [], totals: { debit: 0, credit: 0 } }
    );
    const [endDate, setEndDate] = useState(moment().format("YYYY-MM-DD"));

    useEffect(() => {
        dispatch(fetchTrialBalance({ end_date: endDate }));
    }, [endDate]);

    const rowsByType = (tb.rows || []).reduce((acc, r) => {
        (acc[r.type] = acc[r.type] || []).push(r);
        return acc;
    }, {});

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Trial Balance" />
            <div className="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h4 className="mb-0">Trial Balance</h4>
                <div className="d-flex align-items-center gap-2">
                    <label className="form-label mb-0">As of:</label>
                    <input
                        type="date"
                        className="form-control"
                        style={{ width: 180 }}
                        value={endDate}
                        onChange={(e) => setEndDate(e.target.value)}
                    />
                </div>
            </div>

            <Row className="mb-3">
                <Col md={4}>
                    <Card className="shadow-sm">
                        <Card.Body>
                            <div className="text-muted small">Total Debit</div>
                            <div className="fs-3 fw-bold text-success">
                                {Number(tb.totals?.debit || 0).toLocaleString()}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card className="shadow-sm">
                        <Card.Body>
                            <div className="text-muted small">Total Credit</div>
                            <div className="fs-3 fw-bold text-danger">
                                {Number(tb.totals?.credit || 0).toLocaleString()}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card className="shadow-sm">
                        <Card.Body>
                            <div className="text-muted small">Balanced?</div>
                            <div className="fs-3 fw-bold">
                                {Math.abs(
                                    Number(tb.totals?.debit || 0) -
                                        Number(tb.totals?.credit || 0)
                                ) < 0.01 ? (
                                    <span className="badge bg-success">Yes</span>
                                ) : (
                                    <span className="badge bg-danger">No</span>
                                )}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            <Card className="shadow-sm">
                <Card.Body>
                    <Table responsive bordered hover size="sm">
                        <thead className="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Account</th>
                                <th>Type</th>
                                <th className="text-end">Debit</th>
                                <th className="text-end">Credit</th>
                                <th className="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(tb.rows || []).length === 0 ? (
                                <tr>
                                    <td colSpan="6" className="text-muted text-center py-3">
                                        No accounts with movement yet
                                    </td>
                                </tr>
                            ) : (
                                Object.keys(rowsByType).map((type) => (
                                    <React.Fragment key={type}>
                                        <tr className="table-secondary">
                                            <td colSpan="6" className="fw-semibold text-capitalize">
                                                {type}
                                            </td>
                                        </tr>
                                        {rowsByType[type].map((r) => (
                                            <tr key={r.id}>
                                                <td>{r.code}</td>
                                                <td>{r.name}</td>
                                                <td>
                                                    <span className={typeBadge(r.type)}>
                                                        {r.type}
                                                    </span>
                                                </td>
                                                <td className="text-end">
                                                    {Number(r.total_debit).toFixed(2)}
                                                </td>
                                                <td className="text-end">
                                                    {Number(r.total_credit).toFixed(2)}
                                                </td>
                                                <td className="text-end fw-semibold">
                                                    {Number(r.balance).toFixed(2)}
                                                </td>
                                            </tr>
                                        ))}
                                    </React.Fragment>
                                ))
                            )}
                        </tbody>
                        <tfoot>
                            <tr className="table-light fw-bold">
                                <td colSpan="3" className="text-end">
                                    Totals
                                </td>
                                <td className="text-end">
                                    {Number(tb.totals?.debit || 0).toFixed(2)}
                                </td>
                                <td className="text-end">
                                    {Number(tb.totals?.credit || 0).toFixed(2)}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </Table>
                </Card.Body>
            </Card>
        </MasterLayout>
    );
};

export default TrialBalance;
