import React, { useEffect, useState } from "react";
import { Modal } from "react-bootstrap-v5";
import { useDispatch, useSelector } from "react-redux";
import { getFormattedMessage } from "../../shared/sharedMethod";
import {
    addFbrProfile,
    editFbrProfile,
} from "../../store/action/fbrProfileAction";
import { fetchStore } from "../../store/action/storeAction";

const PROVINCES = [
    "Punjab",
    "Sindh",
    "Khyber Pakhtunkhwa",
    "Balochistan",
    "Islamabad Capital Territory",
    "Azad Jammu & Kashmir",
    "Gilgit-Baltistan",
];

const FbrProfileForm = ({ show, data, handleClose, isEdit }) => {
    const dispatch = useDispatch();
    const { stores } = useSelector((state) => state);
    const [form, setForm] = useState({
        store_id: "",
        business_name: "",
        ntn: "",
        strn: "",
        province: "Punjab",
        business_activity: "",
        pos_id: "",
        sandbox_token: "",
        production_token: "",
        mode: "sandbox",
        enabled: 1,
    });
    const [errors, setErrors] = useState({});

    useEffect(() => {
        if (!stores || stores.length === 0) {
            dispatch(fetchStore());
        }
    }, []);

    useEffect(() => {
        setForm({
            store_id: data?.store_id || "",
            business_name: data?.business_name || "",
            ntn: data?.ntn || "",
            strn: data?.strn || "",
            province: data?.province || "Punjab",
            business_activity: data?.business_activity || "",
            pos_id: data?.pos_id || "",
            sandbox_token: data?.sandbox_token || "",
            production_token: data?.production_token || "",
            mode: data?.mode || "sandbox",
            enabled: data?.enabled !== undefined ? (data.enabled ? 1 : 0) : 1,
        });
        setErrors({});
    }, [data, show]);

    const onChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm((f) => ({
            ...f,
            [name]: type === "checkbox" ? (checked ? 1 : 0) : value,
        }));
    };

    const validate = () => {
        const errs = {};
        if (!form.business_name) errs.business_name = "Business name is required";
        if (!form.ntn) errs.ntn = "NTN is required";
        setErrors(errs);
        return Object.keys(errs).length === 0;
    };

    const buildPayload = () => {
        const fd = new FormData();
        Object.entries(form).forEach(([k, v]) => {
            if (v !== null && v !== undefined) fd.append(k, v);
        });
        if (isEdit) fd.append("_method", "PATCH");
        return fd;
    };

    const onSubmit = () => {
        if (!validate()) return;
        if (isEdit) {
            dispatch(editFbrProfile(data.id, buildPayload(), handleClose));
        } else {
            dispatch(addFbrProfile(buildPayload(), handleClose));
        }
    };

    return (
        <Modal show={show} onHide={handleClose} size="lg" keyboard={false}>
            <Modal.Header closeButton>
                <Modal.Title>
                    {isEdit ? "Edit FBR Profile" : "Create FBR Profile"}
                </Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Store / Branch:</label>
                        <select
                            name="store_id"
                            value={form.store_id || ""}
                            className="form-control"
                            onChange={onChange}
                        >
                            <option value="">-- All stores (tenant level) --</option>
                            {stores &&
                                stores.map((s) => (
                                    <option key={s.id} value={s.id}>
                                        {s?.attributes?.name}
                                    </option>
                                ))}
                        </select>
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">
                            Business Name <span className="required" />
                        </label>
                        <input
                            type="text"
                            name="business_name"
                            value={form.business_name}
                            className="form-control"
                            onChange={onChange}
                        />
                        <span className="text-danger d-block fs-small mt-2">
                            {errors.business_name}
                        </span>
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">
                            NTN <span className="required" />
                        </label>
                        <input
                            type="text"
                            name="ntn"
                            value={form.ntn}
                            className="form-control"
                            onChange={onChange}
                        />
                        <span className="text-danger d-block fs-small mt-2">
                            {errors.ntn}
                        </span>
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">STRN</label>
                        <input
                            type="text"
                            name="strn"
                            value={form.strn}
                            className="form-control"
                            onChange={onChange}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Province</label>
                        <select
                            name="province"
                            value={form.province}
                            className="form-control"
                            onChange={onChange}
                        >
                            {PROVINCES.map((p) => (
                                <option key={p} value={p}>
                                    {p}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Business Activity</label>
                        <input
                            type="text"
                            name="business_activity"
                            value={form.business_activity}
                            placeholder="e.g. Retailer, Manufacturer"
                            className="form-control"
                            onChange={onChange}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">POS ID</label>
                        <input
                            type="text"
                            name="pos_id"
                            value={form.pos_id}
                            className="form-control"
                            onChange={onChange}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Mode</label>
                        <select
                            name="mode"
                            value={form.mode}
                            className="form-control"
                            onChange={onChange}
                        >
                            <option value="sandbox">Sandbox</option>
                            <option value="production">Production</option>
                        </select>
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Sandbox Token</label>
                        <input
                            type="text"
                            name="sandbox_token"
                            value={form.sandbox_token}
                            className="form-control"
                            onChange={onChange}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Production Token</label>
                        <input
                            type="text"
                            name="production_token"
                            value={form.production_token}
                            className="form-control"
                            onChange={onChange}
                        />
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-check form-switch form-switch-sm">
                            <input
                                type="checkbox"
                                name="enabled"
                                checked={!!form.enabled}
                                onChange={onChange}
                                className="me-3 form-check-input cursor-pointer"
                            />
                            <span>Enabled (FBR sync active)</span>
                        </label>
                    </div>
                </div>
            </Modal.Body>
            <Modal.Footer>
                <button className="btn btn-primary" onClick={onSubmit}>
                    {getFormattedMessage("globally.save-btn")}
                </button>
                <button className="btn btn-secondary" onClick={handleClose}>
                    {getFormattedMessage("globally.cancel-btn")}
                </button>
            </Modal.Footer>
        </Modal>
    );
};

export default FbrProfileForm;
