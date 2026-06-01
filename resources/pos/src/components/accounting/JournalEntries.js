import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Modal, Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import moment from "moment";
import {
    fetchJournalEntries,
    saveJournalEntry,
    postJournalEntry,
    fetchAccounts,
} from "../../store/action/accountingAction";

const emptyLine = () => ({ account_id: "", debit: 0, credit: 0, memo: "" });

const NewJournalModal = ({ show, accounts, onHide }) => {
    const dispatch = useDispatch();
    const [f, setF] = useState({
        reference: "",
        entry_date: moment().format("YYYY-MM-DD"),
        description: "",
    });
    const [lines, setLines] = useState([emptyLine(), emptyLine()]);

    useEffect(() => {
        if (show) {
            setF({
                reference: "",
                entry_date: moment().format("YYYY-MM-DD"),
                description: "",
            });
            setLines([emptyLine(), emptyLine()]);
        }
    }, [show]);

    const setLine = (i, k, v) =>
        setLines((arr) => arr.map((l, idx) => (idx === i ? { ...l, [k]: v } : l)));

    const totalDebit = lines.reduce((s, l) => s + Number(l.debit || 0), 0);
    const totalCredit = lines.reduce((s, l) => s + Number(l.credit || 0), 0);
    const balanced = totalDebit > 0 && Math.abs(totalDebit - totalCredit) < 0.01;

    const submit = () => {
        const cleanLines = lines.filter((l) => l.account_id);
        dispatch(saveJournalEntry({ ...f, lines: cleanLines }, onHide));
    };

    return (
        <Modal show={show} onHide={onHide} size="lg">
            <Modal.Header closeButton>
                <Modal.Title>New Journal Entry</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Date</label>
                        <input
                            type="date"
                            className="form-control"
                            value={f.entry_date}
                            onChange={(e) =>
                                setF((s) => ({ ...s, entry_date: e.target.value }))
                            }
                        />
                    </div>
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Reference</label>
                        <input
                            className="form-control"
                            value={f.reference}
                            onChange={(e) =>
                                setF((s) => ({ ...s, reference: e.target.value }))
                            }
                        />
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-label">Description</label>
                        <input
                            className="form-control"
                            value={f.description}
                            onChange={(e) =>
                                setF((s) => ({
                                    ...s,
                                    description: e.target.value,
                                }))
                            }
                        />
                    </div>
                </div>
                <table className="table table-sm">
                    <thead className="table-light">
                        <tr>
                            <th>Account</th>
                            <th className="text-end">Debit</th>
                            <th className="text-end">Credit</th>
                            <th>Memo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {lines.map((l, i) => (
                            <tr key={i}>
                                <td>
                                    <select
                                        className="form-control"
                                        value={l.account_id}
                                        onChange={(e) =>
                                            setLine(i, "account_id", e.target.value)
                                        }
                                    >
                                        <option value="">-- Select --</option>
                                        {accounts.map((a) => (
                                            <option key={a.id} value={a.id}>
                                                {a.code} — {a.name}
                                            </option>
                                        ))}
                                    </select>
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        step="0.01"
                                        className="form-control text-end"
                                        value={l.debit}
                                        onChange={(e) =>
                                            setLine(i, "debit", e.target.value)
                                        }
                                    />
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        step="0.01"
                                        className="form-control text-end"
                                        value={l.credit}
                                        onChange={(e) =>
                                            setLine(i, "credit", e.target.value)
                                        }
                                    />
                                </td>
                                <td>
                                    <input
                                        className="form-control"
                                        value={l.memo}
                                        onChange={(e) =>
                                            setLine(i, "memo", e.target.value)
                                        }
                                    />
                                </td>
                                <td>
                                    <button
                                        className="btn btn-sm btn-outline-danger"
                                        onClick={() =>
                                            setLines((arr) =>
                                                arr.filter((_, idx) => idx !== i)
                                            )
                                        }
                                    >
                                        ×
                                    </button>
                                </td>
                            </tr>
                        ))}
                        <tr>
                            <td>
                                <button
                                    className="btn btn-sm btn-outline-primary"
                                    onClick={() =>
                                        setLines((arr) => [...arr, emptyLine()])
                                    }
                                >
                                    + Add line
                                </button>
                            </td>
                            <td className="fw-bold text-end">
                                {totalDebit.toFixed(2)}
                            </td>
                            <td className="fw-bold text-end">
                                {totalCredit.toFixed(2)}
                            </td>
                            <td colSpan="2">
                                {balanced ? (
                                    <span className="badge bg-light-success">
                                        Balanced
                                    </span>
                                ) : (
                                    <span className="badge bg-light-danger">
                                        Unbalanced
                                    </span>
                                )}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Modal.Body>
            <Modal.Footer>
                <Button onClick={submit} disabled={!balanced}>
                    Save Draft
                </Button>
                <Button variant="secondary" onClick={onHide}>
                    Cancel
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

const statusBadge = (s) => {
    const m = {
        draft: "bg-light-warning",
        posted: "bg-light-success",
        void: "bg-light-secondary",
    };
    return `badge text-capitalize ${m[s] || "bg-light-primary"}`;
};

const JournalEntries = () => {
    const dispatch = useDispatch();
    const entries = useSelector((s) => s.accounting?.journalEntries || []);
    const accounts = useSelector((s) => s.accounting?.accounts || []);
    const isLoading = useSelector((s) => s.isLoading);
    const [show, setShow] = useState(false);

    useEffect(() => {
        dispatch(fetchAccounts());
        dispatch(fetchJournalEntries());
    }, []);

    const columns = [
        {
            name: "Date",
            cell: (r) => moment(r.entry_date).format("YYYY-MM-DD"),
        },
        { name: "Reference", selector: (r) => r.reference || "-" },
        { name: "Description", selector: (r) => r.description || "-" },
        {
            name: "Total",
            cell: (r) => {
                const total = (r.lines || []).reduce(
                    (s, l) => s + Number(l.debit || 0),
                    0
                );
                return <span className="fw-semibold">{total.toFixed(2)}</span>;
            },
        },
        {
            name: "Status",
            cell: (r) => <span className={statusBadge(r.status)}>{r.status}</span>,
        },
        {
            name: "Action",
            right: true,
            cell: (r) =>
                r.status === "draft" ? (
                    <button
                        className="btn btn-sm btn-outline-success"
                        onClick={() => dispatch(postJournalEntry(r.id))}
                    >
                        Post
                    </button>
                ) : (
                    <span className="text-muted">—</span>
                ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Journal Entries" />
            <ReactDataTable
                columns={columns}
                items={entries}
                isLoading={isLoading}
                pagination={false}
                isShowSearch
                onChange={() => dispatch(fetchJournalEntries())}
                AddButton={
                    <div className="text-end">
                        <Button onClick={() => setShow(true)}>+ New Entry</Button>
                    </div>
                }
            />
            <NewJournalModal
                show={show}
                accounts={accounts}
                onHide={() => setShow(false)}
            />
        </MasterLayout>
    );
};

export default JournalEntries;
