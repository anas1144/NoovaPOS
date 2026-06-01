import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Modal, Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import ActionButton from "../../shared/action-buttons/ActionButton";
import DeleteModel from "../../shared/action-buttons/DeleteModel";
import {
    fetchAccounts,
    saveAccount,
    deleteAccount,
} from "../../store/action/accountingAction";

const TYPES = ["asset", "liability", "equity", "revenue", "expense"];

const AccountForm = ({ show, data, accounts, onHide }) => {
    const dispatch = useDispatch();
    const [f, setF] = useState({
        code: "",
        name: "",
        type: "asset",
        parent_id: "",
        is_active: 1,
    });

    useEffect(() => {
        setF({
            code: data?.code || "",
            name: data?.name || "",
            type: data?.type || "asset",
            parent_id: data?.parent_id || "",
            is_active: data?.is_active !== false ? 1 : 0,
        });
    }, [data, show]);

    const set = (k) => (e) => setF((s) => ({ ...s, [k]: e.target.value }));

    return (
        <Modal show={show} onHide={onHide}>
            <Modal.Header closeButton>
                <Modal.Title>
                    {data?.id ? "Edit Account" : "Create Account"}
                </Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-4 mb-3">
                        <label className="form-label">Code</label>
                        <input
                            className="form-control"
                            value={f.code}
                            onChange={set("code")}
                            placeholder="1000"
                        />
                    </div>
                    <div className="col-md-8 mb-3">
                        <label className="form-label">Name</label>
                        <input
                            className="form-control"
                            value={f.name}
                            onChange={set("name")}
                            placeholder="e.g. Cash, Sales Revenue"
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Type</label>
                        <select
                            className="form-control"
                            value={f.type}
                            onChange={set("type")}
                        >
                            {TYPES.map((t) => (
                                <option key={t} value={t}>
                                    {t.charAt(0).toUpperCase() + t.slice(1)}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Parent Account</label>
                        <select
                            className="form-control"
                            value={f.parent_id || ""}
                            onChange={set("parent_id")}
                        >
                            <option value="">-- None --</option>
                            {accounts
                                .filter((a) => a.id !== data?.id)
                                .map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.code} — {a.name}
                                    </option>
                                ))}
                        </select>
                    </div>
                </div>
            </Modal.Body>
            <Modal.Footer>
                <Button onClick={() => dispatch(saveAccount(f, data?.id, onHide))}>
                    Save
                </Button>
                <Button variant="secondary" onClick={onHide}>
                    Cancel
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

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

const ChartOfAccounts = () => {
    const dispatch = useDispatch();
    const accounts = useSelector((s) => s.accounting?.accounts || []);
    const isLoading = useSelector((s) => s.isLoading);
    const [show, setShow] = useState(false);
    const [editData, setEditData] = useState(null);
    const [del, setDel] = useState(null);

    useEffect(() => {
        dispatch(fetchAccounts());
    }, []);

    const columns = [
        { name: "Code", selector: (r) => r.code, sortable: false },
        { name: "Name", selector: (r) => r.name },
        {
            name: "Type",
            cell: (r) => <span className={typeBadge(r.type)}>{r.type}</span>,
        },
        {
            name: "Active",
            cell: (r) =>
                r.is_active ? (
                    <span className="badge bg-light-success">Yes</span>
                ) : (
                    <span className="badge bg-light-secondary">No</span>
                ),
        },
        {
            name: "Action",
            right: true,
            cell: (r) => (
                <ActionButton
                    item={r}
                    goToEditProduct={() => {
                        setEditData(r);
                        setShow(true);
                    }}
                    isEditMode={true}
                    onClickDeleteModel={() => setDel(r)}
                />
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Chart of Accounts" />
            <ReactDataTable
                columns={columns}
                items={accounts}
                isLoading={isLoading}
                pagination={false}
                isShowSearch
                onChange={() => dispatch(fetchAccounts())}
                AddButton={
                    <div className="text-end">
                        <Button
                            onClick={() => {
                                setEditData(null);
                                setShow(true);
                            }}
                        >
                            + New Account
                        </Button>
                    </div>
                }
            />
            <AccountForm
                show={show}
                data={editData}
                accounts={accounts}
                onHide={() => setShow(false)}
            />
            {del && (
                <DeleteModel
                    onClickDeleteModel={() => setDel(null)}
                    deleteModel={true}
                    deleteUserClick={() => {
                        dispatch(deleteAccount(del.id));
                        setDel(null);
                    }}
                    title="Delete Account"
                    name="Account"
                />
            )}
        </MasterLayout>
    );
};

export default ChartOfAccounts;
