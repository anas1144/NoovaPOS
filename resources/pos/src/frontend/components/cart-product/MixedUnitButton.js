import React, { useEffect, useState } from "react";
import { Button, Modal } from "react-bootstrap-v5";
import { useDispatch } from "react-redux";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faLayerGroup } from "@fortawesome/free-solid-svg-icons";
import apiConfig from "../../../config/apiConfig";
import { apiBaseURL, toastType } from "../../../constants";
import { addToast } from "../../../store/action/toastAction";

/**
 * Mixed-unit entry for a cart line (box / pack / piece).
 *
 * The line price is per BASE unit (piece). The cashier enters how many of each
 * unit level they're selling; we convert to a single base quantity using each
 * level's factor_to_base and write it back to the line as `quantity`, plus a
 * `unit_breakdown` map for the receipt/record. The sale-save path is unchanged:
 * total still = product_price (per base unit) × quantity (base units).
 */
const MixedUnitButton = ({ updateProducts, setUpdateProducts }) => {
    const dispatch = useDispatch();
    const [enabled, setEnabled] = useState(false);
    const [show, setShow] = useState(false);
    const [activeId, setActiveId] = useState(null);
    const [levels, setLevels] = useState([]);
    const [counts, setCounts] = useState({});
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        apiConfig
            .get(apiBaseURL.MY_FEATURES)
            .then((res) => setEnabled(!!res.data?.data?.unit_hierarchy))
            .catch(() => {});
    }, []);

    const lines = (updateProducts || []).filter((l) => Number(l.product_id) > 0);

    const selectLine = (line) => {
        setActiveId(line.id);
        setLevels([]);
        setCounts({});
        setLoading(true);
        const url = apiBaseURL.PRODUCT_UNIT_LEVELS.replace("{id}", line.product_id);
        apiConfig
            .get(url)
            .then((res) => {
                const lv = res.data?.data || [];
                setLevels(lv);
                // Seed the existing breakdown if the line already has one.
                const existing = line.unit_breakdown || {};
                const seed = {};
                lv.forEach((l) => (seed[l.name] = Number(existing[l.name]) || 0));
                setCounts(seed);
            })
            .catch(() => setLevels([]))
            .finally(() => setLoading(false));
    };

    const baseQty = levels.reduce(
        (sum, l) => sum + (Number(counts[l.name]) || 0) * Number(l.factor_to_base),
        0
    );

    const apply = () => {
        if (baseQty <= 0) {
            dispatch(addToast({ text: "Enter at least one unit.", type: toastType.ERROR }));
            return;
        }
        const breakdown = {};
        levels.forEach((l) => {
            const c = Number(counts[l.name]) || 0;
            if (c > 0) breakdown[l.name] = c;
        });
        setUpdateProducts(
            (updateProducts || []).map((l) =>
                l.id === activeId
                    ? { ...l, quantity: Number(baseQty.toFixed(4)), unit_breakdown: breakdown }
                    : l
            )
        );
        dispatch(addToast({ text: "Units applied to line." }));
        setActiveId(null);
        setShow(false);
    };

    if (!enabled) return null;

    const activeLine = lines.find((l) => l.id === activeId);

    return (
        <>
            <Button variant="outline-secondary" className="w-100 mb-2" onClick={() => setShow(true)}>
                <FontAwesomeIcon icon={faLayerGroup} className="me-2" />
                Mixed Units
            </Button>

            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>{activeLine ? activeLine.name : "Pick a cart item"}</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    {!activeLine ? (
                        lines.length === 0 ? (
                            <div className="text-center text-muted py-3">Cart is empty.</div>
                        ) : (
                            <div className="list-group">
                                {lines.map((l) => (
                                    <button
                                        key={l.id}
                                        type="button"
                                        className="list-group-item list-group-item-action d-flex justify-content-between"
                                        onClick={() => selectLine(l)}
                                    >
                                        <span>{l.name}</span>
                                        <span className="text-muted">qty {l.quantity}</span>
                                    </button>
                                ))}
                            </div>
                        )
                    ) : loading ? (
                        <div className="text-center text-muted py-3">Loading units…</div>
                    ) : levels.length <= 1 ? (
                        <div className="text-center text-muted py-3">
                            This product has no box/pack hierarchy — enter quantity normally.
                        </div>
                    ) : (
                        <>
                            {levels.map((l) => (
                                <div className="mb-3 d-flex align-items-center justify-content-between" key={l.name}>
                                    <label className="form-label mb-0">
                                        {l.name}
                                        <span className="text-muted ms-2 small">
                                            (= {Number(l.factor_to_base)} base)
                                        </span>
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        className="form-control w-auto"
                                        value={counts[l.name] ?? 0}
                                        onChange={(e) =>
                                            setCounts((c) => ({ ...c, [l.name]: e.target.value }))
                                        }
                                    />
                                </div>
                            ))}
                            <div className="text-end fw-semibold">Base quantity: {Number(baseQty.toFixed(4))}</div>
                        </>
                    )}
                </Modal.Body>
                <Modal.Footer>
                    {activeLine && levels.length > 1 && (
                        <Button variant="primary" onClick={apply}>
                            Apply
                        </Button>
                    )}
                    {activeLine && (
                        <Button variant="link" onClick={() => setActiveId(null)}>
                            Back
                        </Button>
                    )}
                    <Button variant="secondary" onClick={() => setShow(false)}>
                        Close
                    </Button>
                </Modal.Footer>
            </Modal>
        </>
    );
};

export default MixedUnitButton;
