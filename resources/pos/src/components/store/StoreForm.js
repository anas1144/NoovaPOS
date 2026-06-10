import React, { useEffect, useState } from 'react';
import { Modal } from 'react-bootstrap-v5';
import { useDispatch } from 'react-redux';
import { getFormattedMessage, placeholderText } from '../../shared/sharedMethod';
import { addStore, editStore } from '../../store/action/storeAction';
import apiConfig from '../../config/apiConfig';
import { apiBaseURL } from '../../constants';

const StoreForm = ({ show, data, handleClose, title, isEdit }) => {
    const dispatch = useDispatch();
    const [storeValue, setStoreValue] = useState({
        name: "",
        shop_type: "retail",
    });
    const [shopTypes, setShopTypes] = useState([]);
    const [errors, setErrors] = useState({});

    useEffect(() => {
        apiConfig
            .get(apiBaseURL.PUBLIC_SHOP_TYPES)
            .then((res) => setShopTypes(res.data?.data || []))
            .catch(() => {});
    }, []);

    useEffect(() => {
        setStoreValue({
            name: data?.name ? data?.name : "",
            shop_type: data?.shop_type ? data?.shop_type : "retail",
        });
        setErrors({});
    }, [data, show]);

    const handleValidation = () => {
        let errorss = {};
        let isValid = false;
        if (!storeValue.name) {
            errorss["name"] = getFormattedMessage("store.name.validate.label");
        } else {
            isValid = true;
        }
        setErrors(errorss);
        return isValid;
    };

    const onChangeInput = (e) => {
        e.preventDefault();
        setStoreValue(inputs => ({
            ...inputs,
            [e.target.name]: e.target.value
        }));
        setErrors({});
    };

    const prepareFormData = (data) => {
        const formData = new FormData();
        formData.append("name", data.name);
        formData.append("shop_type", data.shop_type || "retail");
        if (isEdit) {
            formData.append("_method", "PATCH");
        }
        return formData;
    };

    const clearData = () => {
        setStoreValue({ name: '', shop_type: 'retail' });
        setErrors({});
    }

    const handleCancelButton = () => {
        clearData();
        handleClose();
    }

    const onSubmit = () => {
        const valid = handleValidation();
        if (valid) {
            if (isEdit) {
                dispatch(editStore(data?.id, prepareFormData(storeValue), handleClose));
            } else {
                dispatch(addStore(prepareFormData(storeValue), handleClose, clearData));
            }
        }
    };

    return (
        <Modal
            show={show}
            onHide={handleClose}
            keyboard={false}
        >
            <Modal.Header closeButton>
                <Modal.Title>{title}</Modal.Title>
            </Modal.Header>
            <Modal.Body className='pb-0'>
                <div className='row'>
                    <div className='col-md-12 mb-3'>
                        <label
                            className='form-label'>{getFormattedMessage("store.name.title")}: </label>
                        <span className='required' />
                        <input
                            type='text'
                            name='name'
                            value={storeValue?.name}
                            placeholder={placeholderText("store.name.placeholder.title")}
                            className='form-control'
                            autoComplete='off'
                            onChange={onChangeInput} />
                        <span className='text-danger d-block fw-400 fs-small mt-2'>{errors['name'] ? errors['name'] : null}</span>
                    </div>
                    <div className='col-md-12 mb-3'>
                        <label className='form-label'>Business / Shop Type:</label>
                        <select
                            name='shop_type'
                            className='form-control'
                            value={storeValue.shop_type}
                            onChange={onChangeInput}
                        >
                            {shopTypes.length === 0 && (
                                <option value='retail'>Retail</option>
                            )}
                            {shopTypes.map((t) => (
                                <option key={t.key} value={t.key}>{t.label}</option>
                            ))}
                        </select>
                        <span className='text-muted fs-small d-block mt-1'>
                            Shops created under this store inherit this type.
                        </span>
                    </div>
                </div>
            </Modal.Body>
            <Modal.Footer className='pt-0'>
                <button
                    className="btn btn-primary mt-4"
                    type="button"
                    onClick={onSubmit}
                >
                    {getFormattedMessage("globally.save-btn")}
                </button>
                <button
                    onClick={handleCancelButton}
                    className='btn btn-secondary mt-4 mx-2'
                    type='button'
                >
                    {getFormattedMessage("globally.cancel-btn")}
                </button>
            </Modal.Footer>
        </Modal>
    );
}

export default StoreForm;
