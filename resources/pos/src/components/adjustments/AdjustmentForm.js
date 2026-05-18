import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import moment from "moment";
import { connect, useDispatch } from "react-redux";
import { fetchProductsByWarehouse } from "../../store/action/productAction";
import { editAdjustment } from "../../store/action/adjustMentAction";
import ProductSearch from "../../shared/components/product-cart/search/ProductSearch";
import AdjustmentRowTable from "../../shared/components/adjustments/AdjustmentRowTable";
import {
    placeholderText,
    getFormattedMessage,
} from "../../shared/sharedMethod";
import ReactDatePicker from "../../shared/datepicker/ReactDatePicker";
import { prepareSaleProductArray } from "../../shared/prepareArray/prepareSaleArray";
import ModelFooter from "../../shared/components/modelFooter";
import { addToast } from "../../store/action/toastAction";
import { toastType } from "../../constants";
import ReactSelect from "../../shared/select/reactSelect";
import { postAdjustment } from "../../store/action/adjustMentAction";
import PostConfirmationModal from "../../shared/components/modals/PostConfirmationModal";

const AdjustmentForm = (props) => {
    const {
        addAdjustmentData,
        editAdjustment,
        postAdjustment,
        id,
        warehouses,
        singleAdjustMent,
        customProducts,
        products,
        fetchProductsByWarehouse,
    } = props;

    const navigate = useNavigate();
    const dispatch = useDispatch();
    const [updateProducts, setUpdateProducts] = useState([]);
    const [isInitialized, setIsInitialized] = useState(false);
    const [quantity, setQuantity] = useState(0);
    const [isPosting, setIsPosting] = useState(false);
    const [showPostConfirm, setShowPostConfirm] = useState(false);
    const [adjustMentValue, setAdjustMentValue] = useState({
        date: new Date(),
        warehouse_id: "",
        notes: singleAdjustMent ? singleAdjustMent.notes : "",
        AdjustmentType: { label: getFormattedMessage("addition.title"), value: 1 },
        posted_status: 0,
    });
    const [errors, setErrors] = useState({
        date: "",
        warehouse_id: "",
        AdjustmentType: "",
    });

    // useEffect( () => {
    //     setUpdateProducts( updateProducts )
    // }, [ updateProducts, quantity ] );

    useEffect(() => {
        updateProducts.length >= 1
            ? dispatch({ type: "DISABLE_OPTION", payload: true })
            : dispatch({ type: "DISABLE_OPTION", payload: false });
    }, [updateProducts]);

    useEffect(() => {
        adjustMentValue.warehouse_id.value &&
            fetchProductsByWarehouse(adjustMentValue?.warehouse_id?.value);
    }, [adjustMentValue.warehouse_id.value]);

    useEffect(() => {
        if (singleAdjustMent) {
            setAdjustMentValue({
                date: singleAdjustMent
                    ? moment(singleAdjustMent.date).toDate()
                    : "",
                warehouse_id: singleAdjustMent
                    ? singleAdjustMent.warehouse_id
                    : "",
                AdjustmentType: singleAdjustMent
                    ? singleAdjustMent.AdjustmentType
                    : "",
                posted_status: singleAdjustMent?.posted_status || 0,
            });
            if (updateProducts.length == 0 && !isInitialized) {
                setUpdateProducts(singleAdjustMent.adjustment_items);
                setIsInitialized(true);
            }
        }
    }, [singleAdjustMent, isInitialized]);

    const handleValidation = () => {
        let error = {};
        let isValid = false;
        const qtyCart = updateProducts.filter((a) => a.quantity == 0);
        if (!adjustMentValue.date) {
            error["date"] = getFormattedMessage("globally.date.validate.label");
        } else if (!adjustMentValue.warehouse_id) {
            error["warehouse_id"] = getFormattedMessage(
                "product.input.warehouse.validate.label"
            );
        } else if (qtyCart.length > 0) {
            dispatch(
                addToast({
                    text: getFormattedMessage(
                        "globally.product-quantity.validate.message"
                    ),
                    type: toastType.ERROR,
                })
            );
        } else if (updateProducts.length < 1) {
            dispatch(
                addToast({
                    text: getFormattedMessage(
                        "purchase.product-list.validate.message"
                    ),
                    type: toastType.ERROR,
                })
            );
        } else {
            isValid = true;
        }
        setErrors(error);
        return isValid;
    };

    const onWarehouseChange = (obj) => {
        setAdjustMentValue((inputs) => ({ ...inputs, warehouse_id: obj }));
        setErrors("");
    };

    const updatedQty = (qty) => {
        setQuantity(qty);
    };

    const handleCallback = (date) => {
        setAdjustMentValue((previousState) => {
            return { ...previousState, date: date };
        });
        setErrors("");
    };

    const prepareFormData = (prepareData) => {
        const formValue = {
            date: moment(prepareData.date).locale('en').toDate(),
            warehouse_id: prepareData.warehouse_id.value
                ? prepareData.warehouse_id.value
                : prepareData.warehouse_id,
            note: prepareData.notes,
            adjustment_items: updateProducts.map((item) => {
                return {
                    product_id: item.product_id,
                    quantity: item.quantity,
                    method_type: item.adjustMethod,
                    adjustment_item_id: item.adjustment_item_id,
                };
            }),
        };
        return formValue;
    };

    const onSubmit = (event) => {
        event.preventDefault();
        const valid = handleValidation();
        if (valid) {
            if (singleAdjustMent) {
                editAdjustment(id, prepareFormData(adjustMentValue), navigate);
            } else {
                addAdjustmentData(prepareFormData(adjustMentValue));
                setAdjustMentValue(adjustMentValue);
            }
        }
    };

    const handlePostAdjustment = () => {
        if (adjustMentValue.posted_status === 1) {
            dispatch(
                addToast({
                    text: getFormattedMessage("globally.post.already_posted") || "This adjustment has already been posted.",
                    type: toastType.WARNING,
                })
            );
            return;
        }
        setShowPostConfirm(true);
    };

    const handleConfirmPost = () => {
        setIsPosting(true);
        postAdjustment(id, navigate, () => {
            setIsPosting(false);
            setShowPostConfirm(false);
        });
    };

    return (
        <div className="card">
            <div className="card-body">
                {/*<Form>*/}
                <div className="row">
                    <div className="col-md-4">
                        <ReactSelect
                            name="warehouse_id"
                            data={warehouses}
                            onChange={onWarehouseChange}
                            title={getFormattedMessage("warehouse.title")}
                            errors={errors["warehouse_id"]}
                            defaultValue={adjustMentValue.warehouse_id}
                            value={adjustMentValue.warehouse_id}
                            addSearchItems={singleAdjustMent}
                            isWarehouseDisable={true}
                            placeholder={placeholderText(
                                "purchase.select.warehouse.placeholder.label"
                            )}
                        />
                    </div>
                    <div className="col-md-4">
                        <label className="form-label fs-6 text-gray-700 mb-3">
                            {getFormattedMessage(
                                "react-data-table.date.column.label"
                            )}
                            :
                        </label>
                        <span className="required" />
                        <div className="position-relative">
                            <ReactDatePicker
                                onChangeDate={handleCallback}
                                newStartDate={adjustMentValue.date}
                            />
                        </div>
                        <span className="text-danger d-block fw-400 fs-small mt-2">
                            {errors["date"] ? errors["date"] : null}
                        </span>
                    </div>
                    <div className="mb-10">
                        <label className="form-label">
                            {getFormattedMessage("product.title")}:
                        </label>
                        <ProductSearch
                            values={adjustMentValue}
                            products={products}
                            handleValidation={handleValidation}
                            updateProducts={updateProducts}
                            isAllProducts={true}
                            setUpdateProducts={setUpdateProducts}
                            customProducts={customProducts}
                        />
                    </div>
                    <div className="custom-responsive">
                        <label className="form-label">
                            {getFormattedMessage(
                                "purchase.order-item.table.label"
                            )}
                            :
                        </label>
                        <span className="required" />
                        <AdjustmentRowTable
                            updateProducts={updateProducts}
                            setUpdateProducts={setUpdateProducts}
                            updatedQty={updatedQty}
                            warehouse={adjustMentValue.warehouse_id}
                        />
                    </div>
                    {singleAdjustMent && (
                        <div className="alert alert-info mb-3">
                            <strong>Status:</strong>{" "}
                            <span className={adjustMentValue.posted_status === 1 ? "text-success fw-bold" : "text-warning fw-bold"}>
                                {adjustMentValue.posted_status === 1 ? getFormattedMessage("globally.status.posted") : getFormattedMessage("globally.status.draft")}
                            </span>
                        </div>
                    )}
                    <div className="d-flex mt-5 justify-content-end gap-2">
                        <button
                            onClick={onSubmit}
                            className="btn btn-primary"
                            type="submit"
                        >
                            {placeholderText("globally.save-btn")}
                        </button>
                        {singleAdjustMent && adjustMentValue.posted_status === 0 && (
                            <button
                                onClick={handlePostAdjustment}
                                className="btn btn-success"
                                type="button"
                                disabled={isPosting}
                            >
                                {isPosting ? getFormattedMessage("globally.post.posting") || "Posting..." : getFormattedMessage("globally.post.button") || "Post Adjustment"}
                            </button>
                        )}
                        <a href="#/app/adjustments" className="btn btn-secondary">
                            {getFormattedMessage("globally.cancel-btn")}
                        </a>
                    </div>
                </div>
                {/*</Form>*/}
            </div>
            {showPostConfirm && (
                <PostConfirmationModal
                    onConfirm={handleConfirmPost}
                    onCancel={() => setShowPostConfirm(false)}
                    title={getFormattedMessage("globally.post.label") || "Post Adjustment"}
                />
            )}
        </div>
    );
};

const mapStateToProps = (state) => {
    const { purchaseProducts, products } = state;
    return {
        customProducts: prepareSaleProductArray(products),
        purchaseProducts,
        products
    };
};

export default connect(mapStateToProps, {
    editAdjustment,
    postAdjustment,
    fetchProductsByWarehouse,
})(AdjustmentForm);
