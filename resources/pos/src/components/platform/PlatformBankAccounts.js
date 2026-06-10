import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Modal, Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL } from "../../constants";
import {
    fetchBankAccounts,
    addBankAccount,
    editBankAccount,
    deleteBankAccount,
} from "../../store/action/platformAction";

const empty = {
    country: "",
    bank_name: "",
    account_title: "",
    account_number: "",
    iban: "",
    swift: "",
    currency: "",
    instructions: "",
    status: true,
};

const BankAccountModal = ({ show, onHide, editData, countries, currencies }) => {
    const dispatch = useDispatch();
    const [form, setForm] = useState(empty);
    const [errors, setErrors] = useState({});

    useEffect(() => {
        setForm(editData ? { ...empty, ...editData } : empty);
        setErrors({});
    }, [editData, show]);

    const setF = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));

    const validate = () => {
        const e = {};
        if (!form.country) e.country = "Country is required";
        if (!form.bank_name) e.bank_name = "Bank name is required";
        if (!form.account_title) e.account_title = "Account title is required";
        setErrors(e);
        return Object.keys(e).length === 0;
    };

    const submit = () => {
        if (!validate()) return;
        if (editData) dispatch(editBankAccount(editData.id, form, onHide));
        else dispatch(addBankAccount(form, onHide));
    };

    return (
        <Modal show={show} onHide={onHide} size="lg">
            <Modal.Header closeButton>
                <Modal.Title>
                    {editData ? "Edit Bank Account" : "New Bank Account"}
                </Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Country</label>
                        <select
                            className="form-control"
                            value={form.country}
                            onChange={setF("country")}
                        >
                            <option value="">-- Select country --</option>
                            {countries.map((c) => (
                                <option key={c.id} value={c.short_code}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                        <span className="text-danger fs-small">{errors.country}</span>
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Currency</label>
                        <select className="form-control" value={form.currency || ""} onChange={setF("currency")}>
                            <option value="">-- Select currency --</option>
                            {currencies.map((c) => (
                                <option key={c.code} value={c.code}>
                                    {c.code} · {c.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Bank Name</label>
                        <input className="form-control" value={form.bank_name} onChange={setF("bank_name")} />
                        <span className="text-danger fs-small">{errors.bank_name}</span>
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Account Title</label>
                        <input className="form-control" value={form.account_title} onChange={setF("account_title")} />
                        <span className="text-danger fs-small">{errors.account_title}</span>
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Account Number</label>
                        <input className="form-control" value={form.account_number || ""} onChange={setF("account_number")} />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">IBAN</label>
                        <input className="form-control" value={form.iban || ""} onChange={setF("iban")} />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">SWIFT / BIC</label>
                        <input className="form-control" value={form.swift || ""} onChange={setF("swift")} />
                    </div>
                    <div className="col-md-6 mb-3 d-flex align-items-end">
                        <label className="form-check form-switch">
                            <input
                                type="checkbox"
                                className="form-check-input"
                                checked={!!form.status}
                                onChange={(e) => setForm((f) => ({ ...f, status: e.target.checked }))}
                            />
                            <span className="ms-2">Active</span>
                        </label>
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-label">Payment Instructions</label>
                        <textarea className="form-control" rows={2} value={form.instructions || ""} onChange={setF("instructions")} />
                    </div>
                </div>
            </Modal.Body>
            <Modal.Footer>
                <Button variant="primary" onClick={submit}>
                    {editData ? "Update" : "Create"}
                </Button>
                <Button variant="secondary" onClick={onHide}>Cancel</Button>
            </Modal.Footer>
        </Modal>
    );
};

const PlatformBankAccounts = () => {
    const dispatch = useDispatch();
    const accounts = useSelector((s) => s.platform?.bankAccounts || []);
    const isLoading = useSelector((s) => s.isLoading);
    const [show, setShow] = useState(false);
    const [editData, setEditData] = useState(null);
    const [countries, setCountries] = useState([]);
    const [currencies, setCurrencies] = useState([]);

    useEffect(() => {
        dispatch(fetchBankAccounts());
        apiConfig
            .get(apiBaseURL.PUBLIC_COUNTRIES)
            .then((res) => setCountries(res.data?.data || []))
            .catch(() => {});
        apiConfig
            .get(apiBaseURL.PUBLIC_CURRENCIES)
            .then((res) => setCurrencies(res.data?.data || []))
            .catch(() => {});
    }, []);

    const columns = [
        { name: "Country", selector: (r) => r.country },
        { name: "Bank", selector: (r) => r.bank_name, wrap: true },
        { name: "Title", selector: (r) => r.account_title, wrap: true },
        { name: "Account #", selector: (r) => r.account_number || "—" },
        { name: "IBAN", selector: (r) => r.iban || "—", wrap: true },
        {
            name: "Status",
            cell: (r) =>
                r.status ? (
                    <span className="badge bg-light-success">Active</span>
                ) : (
                    <span className="badge bg-light-danger">Inactive</span>
                ),
        },
        {
            name: "Action",
            right: true,
            cell: (r) => (
                <div className="d-flex gap-2">
                    <button
                        className="btn btn-sm btn-outline-primary"
                        onClick={() => {
                            setEditData(r);
                            setShow(true);
                        }}
                    >
                        Edit
                    </button>
                    <button
                        className="btn btn-sm btn-outline-danger"
                        onClick={() => dispatch(deleteBankAccount(r.id))}
                    >
                        Delete
                    </button>
                </div>
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Bank Accounts" />
            <ReactDataTable
                columns={columns}
                items={accounts}
                isLoading={isLoading}
                onChange={() => dispatch(fetchBankAccounts())}
                pagination={false}
                isShowSearch
                AddButton={
                    <div className="text-end">
                        <Button
                            onClick={() => {
                                setEditData(null);
                                setShow(true);
                            }}
                        >
                            + New Bank Account
                        </Button>
                    </div>
                }
            />
            <BankAccountModal
                show={show}
                editData={editData}
                countries={countries}
                currencies={currencies}
                onHide={() => setShow(false)}
            />
        </MasterLayout>
    );
};

export default PlatformBankAccounts;
