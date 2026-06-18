import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const OverdueInvoices = () => {
    const dispatch = useDispatch();
    const [data, setData] = useState({ count: 0, balance: 0, rows: [] });
    const [busy, setBusy] = useState(null);

    const toast = (text, type) => dispatch(addToast({ text, type }));

    const load = () => apiConfig.get(apiBaseURL.RECURRING_OVERDUE).then((r) => setData(r.data?.data || { count: 0, balance: 0, rows: [] })).catch(() => {});
    useEffect(() => { load(); }, []);

    const remind = (row) => {
        setBusy(row.id);
        apiConfig.post(`${apiBaseURL.RECURRING_INVOICES}/${row.id}/remind`, {})
            .then(() => { toast(`Reminder sent for ${row.invoice_no}`); load(); })
            .catch(({ response }) => toast(response?.data?.message || "Failed", toastType.ERROR))
            .finally(() => setBusy(null));
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Overdue & Reminders" />

            <div className="row mb-3">
                <div className="col-md-3 col-6 mb-3"><div className="card"><div className="card-body py-3">
                    <div className="text-muted small">Overdue invoices</div>
                    <div className="fw-bold fs-4 text-danger">{data.count}</div>
                </div></div></div>
                <div className="col-md-3 col-6 mb-3"><div className="card"><div className="card-body py-3">
                    <div className="text-muted small">Outstanding balance</div>
                    <div className="fw-bold fs-4">{Number(data.balance).toLocaleString()}</div>
                </div></div></div>
            </div>

            <div className="card">
                <div className="card-header"><h6 className="mb-0">Overdue invoices</h6></div>
                <div className="card-body table-responsive">
                    <table className="table align-middle">
                        <thead><tr>
                            <th>Invoice</th><th>Customer</th><th>Due date</th><th>Days overdue</th><th>Balance</th><th>Last reminder</th><th className="text-end">Action</th>
                        </tr></thead>
                        <tbody>
                            {data.rows.length === 0 ? (
                                <tr><td colSpan={7} className="text-center text-muted py-4">No overdue invoices. 🎉</td></tr>
                            ) : data.rows.map((r) => (
                                <tr key={r.id}>
                                    <td>{r.invoice_no}</td>
                                    <td>{r.customer || "—"}</td>
                                    <td>{r.due_date}</td>
                                    <td><span className="badge bg-light-danger">{r.days_overdue}d</span></td>
                                    <td className="fw-semibold">{Number(r.balance).toLocaleString()}</td>
                                    <td className="small text-muted">{r.reminded_at ? new Date(r.reminded_at).toLocaleString() : "—"}</td>
                                    <td className="text-end">
                                        <Button size="sm" variant="outline-primary" disabled={busy === r.id} onClick={() => remind(r)}>
                                            {busy === r.id ? "Sending…" : "Send reminder"}
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </MasterLayout>
    );
};

export default OverdueInvoices;
