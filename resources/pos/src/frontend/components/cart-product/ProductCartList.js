import React, { useState } from "react";
import { Button } from "react-bootstrap-v5";
import { connect, useDispatch } from "react-redux";
import {
    currencySymbolHandling,
    decimalValidate,
    getFormattedMessage,
} from "../../../shared/sharedMethod";
import { calculateProductCost } from "../../shared/SharedMethod";
import { addToast } from "../../../store/action/toastAction";
import { toastType } from "../../../constants";
import DeleteModel from "../../../shared/action-buttons/DeleteModel";

const ProductCartList = (props) => {
    const {
        singleProduct,
        index,
        onClickUpdateItemInCart,
        onDeleteCartItem,
        frontSetting,
        setUpdateProducts,
        posAllProducts,
        allConfigData,
        settings
    } = props;
    const dispatch = useDispatch();
    const [isQuantityEditable, setIsQuantityEditable] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const productInfo = posAllProducts.find((product) => product.id === singleProduct.id);
    const totalQty = posAllProducts
        .filter((product) => product.id === singleProduct.id)
        .map((product) => product.attributes.stock.quantity);

    const handleIncrement = () => {
        const allowOutOfStockSale = productInfo?.attributes?.allow_out_of_stock_sale === true;
        
        setUpdateProducts((updateProducts) =>
            updateProducts.map((item) => {
                if (item.id === singleProduct.id) {
                    if (!allowOutOfStockSale && item.quantity >= totalQty[0]) {
                        dispatch(
                            addToast({
                                text: getFormattedMessage(
                                    "pos.product-quantity-error.message"
                                ),
                                type: toastType.ERROR,
                            })
                        );
                        return item;
                    } else if (
                        singleProduct.quantity_limit &&
                        item.quantity >= singleProduct.quantity_limit
                    ) {
                        dispatch(
                            addToast({
                                text: getFormattedMessage(
                                    "sale.product-qty.limit.validate.message"
                                ),
                                type: toastType.ERROR,
                            })
                        );
                        return { ...item };
                    } else {
                        return { ...item, quantity: item.quantity++ + 1 };
                    }
                } else {
                    return item;
                }
            })
        );
    };

    const handleDecrement = () => {
        if (singleProduct.quantity - 1 > 0.0) {
            setUpdateProducts((updateProducts) =>
                updateProducts.map((item) =>
                    item.id === singleProduct.id
                        ? { ...item, quantity: item.quantity-- - 1 }
                        : item
                )
            );
        }
    };

    //qty onChange
    const handleChange = (e) => {
        e.preventDefault();
        const { value } = e.target;
        
        // Allow empty string (user clearing the field)
        if (value === '' || value === null) {
            setUpdateProducts((updateProducts) =>
                updateProducts.map((item) =>
                    item.id === singleProduct.id
                        ? { ...item, quantity: '' }
                        : item
                )
            );
            return;
        }

        // check if value includes a decimal point
        if (value.match(/\./g)) {
            const [, decimal] = value.split(".");
            // restrict value to only 2 decimal places
            if (decimal?.length > 3) {
                // do nothing
                return;
            }
        }

        const numValue = Number(value);
        const allowOutOfStockSale = productInfo?.attributes?.allow_out_of_stock_sale === true;

        setUpdateProducts((updateProducts) =>
            updateProducts.map((item) => {
                if (item.id === singleProduct.id) {
                    if (!allowOutOfStockSale && totalQty[0] < numValue) {
                        dispatch(
                            addToast({
                                text: getFormattedMessage(
                                    "pos.product-quantity-error.message"
                                ),
                                type: toastType.ERROR,
                            })
                        );
                        return { ...item, quantity: totalQty[0] };
                    } else if (
                        singleProduct?.quantity_limit &&
                        numValue > singleProduct.quantity_limit
                    ) {
                        dispatch(
                            addToast({
                                text: getFormattedMessage(
                                    "sale.product-qty.limit.validate.message"
                                ),
                                type: toastType.ERROR,
                            })
                        );
                        return { ...item };
                    } else {
                        return {
                            ...item,
                            quantity: numValue,
                        };
                    }
                } else {
                    return item;
                }
            })
        );
    };

    // Handle blur event to ensure valid quantity
    const handleBlur = (e) => {
        const { value } = e.target;
        
        // If empty or 0, set to 1
        if (value === '' || value === null || Number(value) <= 0) {
            setUpdateProducts((updateProducts) =>
                updateProducts.map((item) =>
                    item.id === singleProduct.id
                        ? { ...item, quantity: 1 }
                        : item
                )
            );
        }
    };

    return (
        <>
            <tr key={index} className="align-middle">
            <td className="text-nowrap text-nowrap ps-0">
                <h4 className="product-name text-gray-900 mb-1 text-capitalize text-truncate">
                    {singleProduct.name}
                </h4>
                <span className="product-sku">
                    <span className="badge bg-light-info sku-badge">
                        {singleProduct.code}
                    </span>
                    <i
                        className="bi bi-pencil-fill text-gray-600 ms-2 cursor-pointer fs-small"
                        onClick={() => onClickUpdateItemInCart(singleProduct)}
                    />
                </span>
            </td>
            <td>
                <div className="d-flex align-items-center gap-2">
                    <div className="counter d-flex align-items-center pos-custom-qty">
                        <Button
                            type="button"
                            variant="primary"
                            onClick={() => handleDecrement()}
                            className="counter__down d-flex align-items-center justify-content-center"
                            disabled={!isQuantityEditable}
                        >
                            -
                        </Button>
                        <input
                            type="number"
                            value={singleProduct.quantity}
                            className="hide-arrow"
                            onKeyPress={(event) => decimalValidate(event)}
                            onChange={(e) => handleChange(e)}
                            onBlur={(e) => handleBlur(e)}
                            onFocus={(e) => e.target.select()}
                            min="0"
                            step="0.01"
                            readOnly={!isQuantityEditable}
                            style={{ cursor: isQuantityEditable ? 'text' : 'not-allowed', opacity: isQuantityEditable ? 1 : 0.6 }}
                        />
                        <Button
                            type="button"
                            variant="primary"
                            onClick={() => handleIncrement()}
                            className="counter__up d-flex align-items-center justify-content-center"
                            disabled={!isQuantityEditable}
                        >
                            +
                        </Button>
                    </div>
                    <Button
                        type="button"
                        variant={isQuantityEditable ? "success" : "secondary"}
                        size="sm"
                        onClick={() => setIsQuantityEditable(!isQuantityEditable)}
                        className="d-flex align-items-center justify-content-center"
                        title={isQuantityEditable ? "Lock quantity" : "Unlock quantity to edit"}
                        style={{ minWidth: '32px', height: '32px' }}
                    >
                        <i className={`bi ${isQuantityEditable ? 'bi-unlock-fill' : 'bi-lock-fill'}`} />
                    </Button>
                </div>
            </td>
            <td className="text-nowrap">
                {currencySymbolHandling(
                    allConfigData,
                    frontSetting.value && frontSetting.value.currency_symbol,
                    calculateProductCost(singleProduct)
                )}
            </td>
            <td className="text-nowrap">
                {currencySymbolHandling(
                    allConfigData,
                    frontSetting.value && frontSetting.value.currency_symbol,
                    calculateProductCost(singleProduct) * singleProduct.quantity
                )}
            </td>
            <td className="text-end remove-button pe-4">
                <Button
                    className="p-0 bg-transparent border-0"
                    onClick={() => setShowDeleteModal(true)}
                >
                    <i className="bi bi-trash3 text-danger" />
                </Button>
            </td>
            </tr>
            {showDeleteModal && (
                <DeleteModel
                    onClickDeleteModel={() => setShowDeleteModal(false)}
                    deleteUserClick={() => {
                        setShowDeleteModal(false);
                        onDeleteCartItem(singleProduct.id);
                    }}
                    name={singleProduct.name}
                />
            )}
        </>
    );
};

export default connect(null, null)(ProductCartList);
