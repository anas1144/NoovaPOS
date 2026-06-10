import React, { useEffect, useState } from "react";
import { Modal } from "react-bootstrap-v5";
import { useDispatch, useSelector } from "react-redux";
import {
    getFormattedMessage,
    placeholderText,
} from "../../shared/sharedMethod";
import { addShop, editShop } from "../../store/action/shopAction";
import { fetchStore } from "../../store/action/storeAction";

const SHOP_TYPES = [
    { value: "retail", label: "Retail" },
    { value: "restaurant", label: "Restaurant" },
    { value: "pharmacy", label: "Pharmacy" },
    { value: "water_supply", label: "Water Supply" },
    { value: "monthly_service", label: "Monthly Service" },
    { value: "warehouse", label: "Warehouse" },
    { value: "bakery", label: "Bakery" },
    { value: "electronics", label: "Electronics" },
    { value: "fashion", label: "Fashion" },
    { value: "distribution", label: "Distribution" },
    { value: "custom", label: "Custom" },
];

const ShopForm = ({ show, data, handleClose, title, isEdit }) => {
    const dispatch = useDispatch();
    const { stores } = useSelector((state) => state);
    const [shopValue, setShopValue] = useState({
        name: "",
        code: "",
        store_id: "",
        shop_type: "retail",
        status: 1,
    });

    const [errors, setErrors] = useState({});

    useEffect(() => {
        if (!stores || stores.length === 0) {
            dispatch(fetchStore());
        }
    }, []);

    useEffect(() => {
        setShopValue({
            name: data?.name ? data?.name : "",
            code: data?.code ? data?.code : "",
            store_id: data?.store_id ? data?.store_id : "",
            shop_type: data?.shop_type ? data?.shop_type : "retail",
            status: data?.status !== undefined ? (data.status ? 1 : 0) : 1,
        });
        setErrors({});
    }, [data, show]);

    const handleValidation = () => {
        let errs = {};
        let isValid = true;
        if (!shopValue.name) {
            errs["name"] = "Shop name is required";
            isValid = false;
        }
        if (!shopValue.store_id) {
            errs["store_id"] = "Store / branch is required";
            isValid = false;
        }
        setErrors(errs);
        return isValid;
    };

    const onChangeInput = (e) => {
        e.preventDefault();
        setShopValue((inputs) => ({
            ...inputs,
            [e.target.name]: e.target.value,
        }));
        setErrors({});
    };

    // Selecting a store locks the shop's type to that store's type.
    const onChangeStore = (e) => {
        const storeId = e.target.value;
        const selected = (stores || []).find(
            (s) => String(s.id) === String(storeId)
        );
        setShopValue((inputs) => ({
            ...inputs,
            store_id: storeId,
            shop_type: selected?.attributes?.shop_type || inputs.shop_type || "retail",
        }));
        setErrors({});
    };

    const prepareFormData = (data) => {
        const fd = new FormData();
        fd.append("name", data.name);
        fd.append("code", data.code || "");
        fd.append("store_id", data.store_id);
        fd.append("shop_type", data.shop_type);
        fd.append("status", data.status ? 1 : 0);
        if (isEdit) fd.append("_method", "PATCH");
        return fd;
    };

    const clearData = () => {
        setShopValue({
            name: "",
            code: "",
            store_id: "",
            shop_type: "retail",
            status: 1,
        });
        setErrors({});
    };

    const handleCancelButton = () => {
        clearData();
        handleClose();
    };

    const onSubmit = () => {
        if (handleValidation()) {
            if (isEdit) {
                dispatch(editShop(data?.id, prepareFormData(shopValue), handleClose));
            } else {
                dispatch(addShop(prepareFormData(shopValue), handleClose, clearData));
            }
        }
    };

    return (
        <Modal show={show} onHide={handleClose} keyboard={false}>
            <Modal.Header closeButton>
                <Modal.Title>{title}</Modal.Title>
            </Modal.Header>
            <Modal.Body className="pb-0">
                <div className="row">
                    <div className="col-md-12 mb-3">
                        <label className="form-label">
                            Store / Branch:
                            <span className="required" />
                        </label>
                        <select
                            name="store_id"
                            className="form-control"
                            value={shopValue.store_id || ""}
                            onChange={onChangeStore}
                        >
                            <option value="">-- Select store --</option>
                            {stores &&
                                stores.map((s) => (
                                    <option key={s.id} value={s.id}>
                                        {s?.attributes?.name}
                                    </option>
                                ))}
                        </select>
                        <span className="text-danger d-block fw-400 fs-small mt-2">
                            {errors["store_id"]}
                        </span>
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-label">
                            Shop / Counter Name:
                            <span className="required" />
                        </label>
                        <input
                            type="text"
                            name="name"
                            value={shopValue.name}
                            placeholder="e.g. Bakery Counter, POS Terminal 01"
                            className="form-control"
                            autoComplete="off"
                            onChange={onChangeInput}
                        />
                        <span className="text-danger d-block fw-400 fs-small mt-2">
                            {errors["name"]}
                        </span>
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Code:</label>
                        <input
                            type="text"
                            name="code"
                            value={shopValue.code}
                            placeholder="Counter code"
                            className="form-control"
                            autoComplete="off"
                            onChange={onChangeInput}
                        />
                    </div>
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Shop Type:</label>
                        <input
                            type="text"
                            className="form-control bg-light text-dark text-capitalize"
                            value={(
                                SHOP_TYPES.find((t) => t.value === shopValue.shop_type)?.label
                            ) || (shopValue.shop_type || "retail").replace(/_/g, " ")}
                            readOnly
                            disabled
                        />
                        <span className="text-muted fs-small d-block mt-1">
                            Inherited from the selected store (locked).
                        </span>
                    </div>
                    <div className="col-md-12 mb-3">
                        <label className="form-check form-switch form-switch-sm">
                            <input
                                type="checkbox"
                                checked={!!shopValue.status}
                                onChange={(e) =>
                                    setShopValue((s) => ({
                                        ...s,
                                        status: e.target.checked ? 1 : 0,
                                    }))
                                }
                                className="me-3 form-check-input cursor-pointer"
                            />
                            <span>Active</span>
                        </label>
                    </div>
                </div>
            </Modal.Body>
            <Modal.Footer className="pt-0">
                <button
                    className="btn btn-primary mt-4"
                    type="button"
                    onClick={onSubmit}
                >
                    {getFormattedMessage("globally.save-btn")}
                </button>
                <button
                    onClick={handleCancelButton}
                    className="btn btn-secondary mt-4 mx-2"
                    type="button"
                >
                    {getFormattedMessage("globally.cancel-btn")}
                </button>
            </Modal.Footer>
        </Modal>
    );
};

export default ShopForm;
