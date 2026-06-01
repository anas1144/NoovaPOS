import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Card, Row, Col, Table } from "react-bootstrap";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import moment from "moment";
import { fetchBalanceSheet } from "../../store/action/accountingAction";

const numberFmt = (n) =>
    Number(n || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const Section = ({ title, items, total }) => (
    <Card className="shadow-sm mb-3">
        <Card.Header className="bg-white fw-semibold">{title}</Card.Header>
        <Card.Body>
            <Table size="sm" responsive className="mb-0">
                <tbody>
                    {(items || []).length === 0 ? (
                        <tr>
                            <td className="text-muted text-center py-2">
                                No accounts recorded
                            </td>
                        </tr>
                    ) : (
                        (items || []).map((r) => (
                            <tr key={r.id}>
                                <td>
                                    <small className="text-muted">{r.code}</small>{" "}
                                    {r.name}
                                </td>
                                <td className="text-end">{numberFmt(r.amount)}</td>
                            </tr>
                        ))
                    )}
                </tbody>
                <tfoot>
                    <tr className="fw-bold table-light">
                        <td>Total {title}</td>
                        <td className="text-end">{numberFmt(total)}</td>
                    </tr>
                </tfoot>
            </Table>
        </Card.Body>
    </Card>
);

const BalanceSheet = () => {
    const dispatch = useDispatch();
    const [data, setData] = useState({
        assets: [],
        liabilities: [],
        equity: [],
        retained_earnings: 0,
        totals: {
            assets: 0,
            liabilities: 0,
            equity: 0,
            liabilities_plus_equity: 0,
        },
    });
    const [end, setEnd] = useState(moment().format("YYYY-MM-DD"));

    useEffect(() => {
        (async () => {
            const result = await dispatch(fetchBalanceSheet({ end_date: end }));
            if (result) setData(result);
        })();
    }, [end]);

    const balanced =
        Math.abs(
            Number(data.totals?.assets || 0) -
                Number(data.totals?.liabilities_plus_equity || 0)
        ) < 0.01;

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Balance Sheet" />
            <div className="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h4 className="mb-0">Balance Sheet</h4>
                <div className="d-flex align-items-center gap-2">
                    <label className="form-label mb-0">As of</label>
                    <input
                        type="date"
                        className="form-control"
                        style={{ width: 180 }}
                        value={end}
                        onChange={(e) => setEnd(e.target.value)}
                    />
                </div>
            </div>

            <Row className="mb-3">
                <Col md={4}>
                    <Card className="shadow-sm">
                        <Card.Body>
                            <div className="text-muted small">Total Assets</div>
                            <div className="fs-3 fw-bold text-success">
                                {numberFmt(data.totals?.assets)}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card className="shadow-sm">
                        <Card.Body>
                            <div className="text-muted small">
                                Liabilities + Equity
                            </div>
                            <div className="fs-3 fw-bold text-primary">
                                {numberFmt(data.totals?.liabilities_plus_equity)}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
                <Col md={4}>
                    <Card className="shadow-sm">
                        <Card.Body>
                            <div className="text-muted small">Balanced?</div>
                            <div className="fs-3 fw-bold">
                                {balanced ? (
                                    <span className="badge bg-success">Yes</span>
                                ) : (
                                    <span className="badge bg-danger">No</span>
                                )}
                            </div>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>

            <Row>
                <Col md={6}>
                    <Section
                        title="Assets"
                        items={data.assets}
                        total={data.totals?.assets}
                    />
                </Col>
                <Col md={6}>
                    <Section
                        title="Liabilities"
                        items={data.liabilities}
                        total={data.totals?.liabilities}
                    />
                    <Card className="shadow-sm">
                        <Card.Header className="bg-white fw-semibold">Equity</Card.Header>
                        <Card.Body>
                            <Table size="sm" responsive className="mb-0">
                                <tbody>
                                    {(data.equity || []).map((r) => (
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
                                    ))}
                                    <tr>
                                        <td>Retained Earnings (period)</td>
                                        <td className="text-end">
                                            {numberFmt(data.retained_earnings)}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr className="fw-bold table-light">
                                        <td>Total Equity</td>
                                        <td className="text-end">
                                            {numberFmt(data.totals?.equity)}
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

export default BalanceSheet;
