import React, { useEffect, useState } from "react";
import { Button, Modal } from "react-bootstrap-v5";
import { useDispatch } from "react-redux";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faGift } from "@fortawesome/free-solid-svg-icons";
import apiConfig from "../../../config/apiConfig";
import { apiBaseURL, toastType } from "../../../constants";
import { addToast } from "../../../store/action/toastAction";

/**
 * Add a deal/combo to the POS cart.
 *
 * A deal is a fixed-price bundle of products. We expand it into normal cart
 * lines (one per product) so the existing sale-save path stays unchanged —
 * every line still carries a real product_id, quantity and product_price.
 *
 * To make the bundle total equal the deal price, each line's unit price is the
 * product's normal price scaled by (deal_price / sum_of_normal_line_totals).
 * The backend simply multiplies product_price * quantity, so the lines sum to
 * the deal price (within rounding). No discount/tax trickery, no schema change.
 */
const AddDealButton = ({ updateProducts, setUpdateProducts }) => {
    const dispatch = useDispatch();
    const [enabled, setEnabled] = useState(false);
    const [show, setShow] = useState(false);
    const [deals, setDeals] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        // Only offer deals when the feature is enabled for this store.
        apiConfig
            .get(apiBaseURL.MY_FEATURES)
            .then((res) => setEnabled(!!res.data?.data?.deals))
            .catch(() => {});
    }, []);

    useEffect(() => {
        if (!show) return;
        setLoading(true);
        apiConfig
            .get(apiBaseURL.DEALS)
            .then((res) =>
                setDeals((res.data?.data || []).filter((d) => d.status ?? d.attributes?.status ?? true))
            )
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [show]);

    // Build the priced cart lines for a deal.
    const buildDealLines = (deal) => {
        const dealAttr = deal.attributes || deal;
        const dealPrice = Number(dealAttr.price);
        const items = (dealAttr.items || deal.items || []).map((it) => {
            const prod = it.product?.attributes || it.product || {};
            return {
                product_id: it.product_id ?? it.product?.id ?? prod.id,
                name: prod.name || "Item",
                normal: Number(prod.product_price ?? 0),
                quantity: Number(it.quantity) || 1,
            };
        });

        const sumNormal = items.reduce((s, it) => s + it.normal * it.quantity, 0);
        // If we have no reference prices, split the deal price evenly per unit.
        const totalUnits = items.reduce((s, it) => s + it.quantity, 0) || 1;
        const factor = sumNormal > 0 ? dealPrice / sumNormal : null;

        return items
            .filter((it) => it.product_id)
            .map((it) => {
                const unit =
                    factor !== null
                        ? Number((it.normal * factor).toFixed(2))
                        : Number((dealPrice / totalUnits).toFixed(2));
                return {
                    name: `${it.name} (deal)`,
                    code: "",
                    product_id: it.product_id,
                    id: `deal_${deal.id}_${it.product_id}`,
                    product_cost: 0,
                    net_unit_cost: unit,
                    product_price: unit,
                    base_price: it.normal,
                    tier_prices: {},
                    tax_type: 1,
                    tax_value: 0,
                    tax_amount: 0,
                    discount_type: 1,
                    discount_value: 0,
                    discount_amount: 0,
                    product_unit: 0,
                    sale_unit: 0,
                    quantity: it.quantity,
                    sub_total: 0,
                    sale_id: 1,
                    hold_item_id: "",
                    warehouse_id: 0,
                    deal_id: deal.id,
                };
            });
    };

    const addDeal = (deal) => {
        const lines = buildDealLines(deal);
        if (lines.length === 0) {
            dispatch(addToast({ text: "This deal has no products.", type: toastType.ERROR }));
            return;
        }
        setUpdateProducts([...(updateProducts || []), ...lines]);
        dispatch(addToast({ text: `Added deal: ${deal.attributes?.name || deal.name}` }));
        setShow(false);
    };

    if (!enabled) return null;

    return (
        <>
            <Button variant="outline-primary" className="w-100 mb-2" onClick={() => setShow(true)}>
                <FontAwesomeIcon icon={faGift} className="me-2" />
                Add Deal
            </Button>

            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Add a deal to the cart</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {loading ? (
                        <div className="text-center text-muted py-3">Loading deals…</div>
                    ) : deals.length === 0 ? (
                        <div className="text-center text-muted py-3">No active deals.</div>
                    ) : (
                        <div className="list-group">
                            {deals.map((deal) => {
                                const a = deal.attributes || deal;
                                return (
                                    <button
                                        key={deal.id}
                                        type="button"
                                        className="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                        onClick={() => addDeal(deal)}
                                    >
                                        <span>
                                            <strong>{a.name}</strong>
                                            {a.code ? <span className="text-muted ms-2">{a.code}</span> : null}
                                        </span>
                                        <span className="badge bg-primary rounded-pill">{a.price}</span>
                                    </button>
                                );
                            })}
                        </div>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShow(false)}>
                        Close
                    </Button>
                </Modal.Footer>
            </Modal>
        </>
    );
};

export default AddDealButton;
