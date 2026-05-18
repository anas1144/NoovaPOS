import React, { useEffect, useRef, useState } from "react";
import { Card, Badge } from "react-bootstrap-v5";
import { connect, useDispatch } from "react-redux";
import { posFetchProduct } from "../../../store/action/pos/posfetchProductAction";
import { posAllProduct } from "../../../store/action/pos/posAllProductAction";
import productImage from "../../../assets/images/brand_logo.png";
import { addToast } from "../../../store/action/toastAction";
import {
    currencySymbolHandling,
    getFormattedMessage,
} from "../../../shared/sharedMethod";
import { toastType } from "../../../constants";
import Skelten from "../../../shared/components/loaders/Skelten";

const Product = (props) => {
    const {
        posAllProducts,
        cartProducts,
        updateCart,
        customCart,
        cartProductIds,
        setCartProductIds,
        settings,
        productMsg,
        newCost,
        selectedOption,
        allConfigData,
        isLoading,
    } = props;
    const [updateProducts, setUpdateProducts] = useState([]);
    const clickAudioRef = useRef(null);
    const dispatch = useDispatch();

    useEffect(() => {
        // update cart while cart is updated
        cartProducts && setUpdateProducts(cartProducts);
        const ids = updateProducts.map((item) => {
            return item.id;
        });
        setCartProductIds(ids);
    }, [updateProducts, cartProducts]);

    const addToCart = (product) => {
        const isOutOfStock = product.attributes.stock.quantity <= 0;
        const allowOutOfStockSale = product.attributes?.allow_out_of_stock_sale === true;
        
        if (isOutOfStock && !allowOutOfStockSale) {
            dispatch(
                addToast({
                    text: getFormattedMessage(
                        "pos.quantity.exceeds.quantity.available.in.stock.message"
                    ),
                    type: toastType.ERROR,
                })
            );
            return;
        }

        if (settings?.attributes?.enable_pos_click_audio === 'true' && clickAudioRef.current) {
            clickAudioRef.current.play().catch((e) => {
                console.warn("Audio play failed:", e);
            });
        }
        addProductToCart(product);
    };

    const addProductToCart = (product) => {
        const newId = posAllProducts
            .filter((item) => item.id === product.id)
            .map((item) => item.id);
        const finalIdArrays = customCart.map((id) => id.product_id);
        const finalId = finalIdArrays.filter(
            (finalIdArray) => finalIdArray === newId[0]
        );
        const pushArray = [...customCart];
        const newProduct = pushArray.find(
            (element) => element.id === finalId[0]
        );
        const filterQty = updateProducts
            .filter((item) => item.id === product.id)
            .map((qty) => qty.quantity)[0];
        if (
            updateProducts.filter((item) => item.id === product.id).length > 0
        ) {
            const allowOutOfStockSale = product.attributes?.allow_out_of_stock_sale === true;
    
            if (!allowOutOfStockSale && filterQty >= product.attributes.stock.quantity) {
                dispatch(
                    addToast({
                        text: getFormattedMessage(
                            "pos.quantity.exceeds.quantity.available.in.stock.message"
                        ),
                        type: toastType.ERROR,
                    })
                );
            } else if (product.attributes.quantity_limit && filterQty >= product.attributes.quantity_limit) {
                dispatch(
                    addToast({
                        text: getFormattedMessage(
                            "sale.product-qty.limit.validate.message"
                        ),
                        type: toastType.ERROR,
                    })
                );
            } else {
                setUpdateProducts((updateProducts) =>
                    updateProducts.map((item) =>
                        item.id === product.id
                            ? {
                                ...item,
                                quantity:
                                    (allowOutOfStockSale || product.attributes.stock.quantity > item.quantity)
                                        ? item.quantity++ + 1
                                        : null,
                            }
                            : { ...item, id: item.id }
                    )
                );
                updateCart(updateProducts, {...product,warehouse_id: selectedOption.value, image: product.attributes.images.imageUrls ? product.attributes.images.imageUrls[0] : productImage});
            }
        } else {
                const newCartItem = {...product, warehouse_id: selectedOption.value, quantity: 1};
                const newCartProduct = {...newProduct, warehouse_id: selectedOption.value, quantity: 1, image: product.attributes.images.imageUrls ? product.attributes.images.imageUrls[0] : productImage};
                
                setUpdateProducts((prevSelected) => [...prevSelected, newCartItem]);
                updateCart((prevSelected) => [...prevSelected, newCartProduct]);
        }
    };

    const isProductExistInCart = (productId) => {
        return cartProductIds.includes(productId);
    };

    const posFilterProduct = posAllProducts && (() => {
        const showOutOfStockProducts = settings?.attributes?.show_pos_stock_product === 'true';
        
        let filtered = showOutOfStockProducts ? posAllProducts : posAllProducts.filter(
            (product) => product.attributes.stock.quantity > 0 || product.attributes?.allow_out_of_stock_sale === true
        );
        
        // Sort by product name (description) A-Z
        return filtered.sort((a, b) => {
            const nameA = (a.attributes?.name || '').toLowerCase();
            const nameB = (b.attributes?.name || '').toLowerCase();
            return nameA.localeCompare(nameB);
        });
    })();
    //Cart Item Array
    const loadAllProduct = (product, index) => {

        return (
            <div
                className="product-custom-card"
                key={index}
                onClick={() => addToCart(product)}
            >
                <Card
                    className={`position-relative h-100 ${
                        isProductExistInCart(product.id) ? "product-active" : ""
                    }`}
                >
                    <Card.Img
                        variant="top"
                        src={
                            product.attributes.images.imageUrls
                                ? product.attributes.images.imageUrls[0]
                                : productImage
                        }
                    />
                    <Card.Body className="px-2 pt-2 pb-1 custom-card-body d-flex flex-column justify-content-evenly">
                        <h6 className="product-title mb-0 text-gray-900">
                            {product.attributes?.name}
                            {product.attributes?.code !==
                            product.attributes?.product_code
                                ? ` (${product.attributes?.code}, ${product.attributes?.product_code})`
                                : null}
                        </h6>
                        <div className="d-flex justify-content-between"><span className="fs-small text-gray-700">
                            {product.attributes.code}
                        </span>
                        {product.attributes?.variation_product ? <span className="badge bg-light-info fs-small text-gray-700">
                           {product.attributes?.variation_product?.variation_type_name}
                        </span> : ''}</div>
                        <p className="m-0 item-badges">
                            <Badge
                                bg="info"
                                text="white"
                                className="product-custom-card__card-badge"
                            >
                                1{" "}
                                {product?.attributes?.product_unit_name?.name}
                            </Badge>
                        </p>
                        <p className="m-0 item-badge">
                            <Badge
                                bg="primary"
                                text="white"
                                className="product-custom-card__card-badge"
                            >
                                {currencySymbolHandling(
                                    allConfigData,
                                    settings.attributes &&
                                        settings.attributes.currency_symbol,
                                    newCost
                                        ? newCost
                                        : product.attributes.product_price
                                )}
                            </Badge>
                        </p>
                    </Card.Body>
                </Card>
            </div>
        )
    };

    return (
        <div
            className={`${
                posFilterProduct && posFilterProduct.length === 0
                    ? "d-flex align-items-center justify-content-center"
                    : ""
            } product-list-block pt-1`}
        >
            <audio ref={clickAudioRef} src={settings?.attributes?.click_audio} preload="auto" />
            <div className="d-flex flex-wrap product-list-block__product-block w-100">
                {posFilterProduct && posFilterProduct.length === 0 ? (
                    isLoading ? (
                        <Skelten />
                    ) : (
                        <h4 className="m-auto">
                            {getFormattedMessage(
                                "pos-no-product-available.label"
                            )}
                        </h4>
                    )
                ) : (
                    ""
                )}
                {productMsg && productMsg === 1 ? (
                    <h4 className="m-auto">
                        {getFormattedMessage("pos-no-product-available.label")}
                    </h4>
                ) : (
                    posFilterProduct &&
                    posFilterProduct.map((product, index) => {
                        return loadAllProduct(product, index);
                    })
                )}
            </div>
        </div>
    );
};

const mapStateToProps = (state) => {
    const { posAllProducts, allConfigData, isLoading } = state;
    return { posAllProducts, allConfigData, isLoading };
};

export default connect(mapStateToProps, { posAllProduct, posFetchProduct })(
    Product
);
