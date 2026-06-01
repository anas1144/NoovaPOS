const useElementSize = () => {
    const ref = useRef(null);
    const [size, setSize] = useState({ width: 0, height: 0 });

    useEffect(() => {
        if (!ref.current || typeof ResizeObserver === "undefined") {
            return undefined;
        }

        const observer = new ResizeObserver(([entry]) => {
            if (!entry) return;

            const { width, height } = entry.contentRect;
            setSize({
                width: Math.floor(width),
                height: Math.floor(height),
            });
        });

        observer.observe(ref.current);

        return () => observer.disconnect();
    }, []);

    return [ref, size];
};

/**
 * VirtualizedProductGrid
 * ──────────────────────
 * Drop-in replacement for the flat `.map()` product list in Product.js.
 *
 * Uses react-window <FixedSizeGrid> + react-virtualized-auto-sizer so only
 * the product cards currently visible in the viewport are mounted in the DOM —
 * critical for catalogs of 10 k–100 k+ products.
 *
 * Props
 * ─────
 * products        – filtered/sorted array of product resource objects
 * cartProductIds  – array of product IDs already in cart (for active highlight)
 * onAddToCart     – (product) => void   called when a card is clicked
 * settings        – Redux settings object  (currency_symbol, etc.)
 * allConfigData   – Redux allConfigData  (for currencySymbolHandling)
 * newCost         – optional override price
 * isLoading       – boolean — show skeleton while loading
 */

import React, { useCallback, useEffect, useRef, useState } from "react";
import { Grid } from "react-window";
import { Card, Badge } from "react-bootstrap-v5";
import productImage from "../../../assets/images/brand_logo.png";
import Skelten from "../../../shared/components/loaders/Skelten";
import { currencySymbolHandling, getFormattedMessage } from "../../../shared/sharedMethod";

// ─── layout constants ────────────────────────────────────────────────────────
const MIN_CARD_WIDTH  = 160;   // px  – minimum card width before column shrinks
const CARD_HEIGHT     = 220;   // px  – fixed row height (card + gap)
const GAP             = 8;     // px  – gap between cards (applied via padding)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Single product card — pure component so react-window's itemData trick works
 * without triggering re-renders on unrelated state changes.
 */
const ProductCard = React.memo(function ProductCard({
    product,
    isInCart,
    onAddToCart,
    settings,
    allConfigData,
    newCost,
    style,
}) {
    const handleClick = useCallback(() => onAddToCart(product), [onAddToCart, product]);

    const imgSrc =
        product.attributes?.images?.imageUrls?.[0] ?? productImage;

    const price = currencySymbolHandling(
        allConfigData,
        settings?.attributes?.currency_symbol,
        newCost || product.attributes?.product_price
    );

    return (
        <div
            style={{
                ...style,
                paddingRight: GAP,
                paddingBottom: GAP,
                boxSizing: "border-box",
            }}
        >
            <div
                className="product-custom-card"
                style={{ height: "100%", cursor: "pointer" }}
                onClick={handleClick}
            >
                <Card
                    className={`position-relative h-100${isInCart ? " product-active" : ""}`}
                >
                    <Card.Img variant="top" src={imgSrc} />
                    <Card.Body className="px-2 pt-2 pb-1 custom-card-body d-flex flex-column justify-content-evenly">
                        <h6 className="product-title mb-0 text-gray-900">
                            {product.attributes?.name}
                            {product.attributes?.code !== product.attributes?.product_code
                                ? ` (${product.attributes?.code}, ${product.attributes?.product_code})`
                                : null}
                        </h6>
                        <div className="d-flex justify-content-between">
                            <span className="fs-small text-gray-700">
                                {product.attributes?.code}
                            </span>
                            {product.attributes?.variation_product && (
                                <span className="badge bg-light-info fs-small text-gray-700">
                                    {product.attributes.variation_product.variation_type_name}
                                </span>
                            )}
                        </div>
                        <p className="m-0 item-badges">
                            <Badge bg="info" text="white" className="product-custom-card__card-badge">
                                1 {product?.attributes?.product_unit_name?.name}
                            </Badge>
                        </p>
                        <p className="m-0 item-badge">
                            <Badge bg="primary" text="white" className="product-custom-card__card-badge">
                                {price}
                            </Badge>
                        </p>
                    </Card.Body>
                </Card>
            </div>
        </div>
    );
});

/**
 * The cell renderer passed to FixedSizeGrid.
 * react-window passes { columnIndex, rowIndex, style, data } where `data`
 * is the `itemData` prop — we pack everything there to avoid closures that
 * would break memoisation.
 */
const Cell = ({ columnIndex, rowIndex, style, products, columnCount, cartProductIds, onAddToCart, settings, allConfigData, newCost }) => {
    const index   = rowIndex * columnCount + columnIndex;
    const product = products[index];

    if (!product) return null; // empty cells in the last row

    const isInCart = cartProductIds.includes(product.id);

    return (
        <ProductCard
            key={product.id}
            product={product}
            isInCart={isInCart}
            onAddToCart={onAddToCart}
            settings={settings}
            allConfigData={allConfigData}
            newCost={newCost}
            style={style}
        />
    );
};

// ─── Main exported component ──────────────────────────────────────────────────

const VirtualizedProductGrid = ({
    products = [],
    cartProductIds = [],
    onAddToCart,
    settings,
    allConfigData,
    newCost,
    isLoading,
}) => {
    const [containerRef, { width, height }] = useElementSize();

    // ── empty / loading states ────────────────────────────────────────────────
    if (isLoading) {
        return (
            <div className="product-list-block pt-1 d-flex align-items-center justify-content-center">
                <Skelten />
            </div>
        );
    }

    if (!products.length) {
        return (
            <div className="product-list-block pt-1 d-flex align-items-center justify-content-center">
                <h4 className="m-auto">
                    {getFormattedMessage("pos-no-product-available.label")}
                </h4>
            </div>
        );
    }

    const gridWidth = width || MIN_CARD_WIDTH;
    const gridHeight = height || 600;

    return (
        <div ref={containerRef} className="product-list-block pt-1" style={{ flex: 1, minHeight: 0 }}>
            {(() => {
                    const width = gridWidth;
                    const height = gridHeight;
                    // ── responsive column count ───────────────────────────────
                    const columnCount = Math.max(1, Math.floor(width / MIN_CARD_WIDTH));
                    const columnWidth = Math.floor(width / columnCount);
                    const rowCount    = Math.ceil(products.length / columnCount);

                    // Pack everything into itemData so Cell stays a pure component
                    const cellProps = {
                        products,
                        columnCount,
                        cartProductIds,
                        onAddToCart,
                        settings,
                        allConfigData,
                        newCost,
                    };

                    return (
                        <Grid
                            columnCount={columnCount}
                            columnWidth={columnWidth}
                            rowCount={rowCount}
                            rowHeight={CARD_HEIGHT}
                            cellComponent={Cell}
                            cellProps={cellProps}
                            overscanCount={3}
                            style={{ height: height || 600, width, overflowX: "hidden" }}
                        />
                    );
                })()}
        </div>
    );
};

// Wrap in React.memo so the whole grid only re-renders when products /
// cartProductIds / settings actually change.
export default React.memo(VirtualizedProductGrid);
