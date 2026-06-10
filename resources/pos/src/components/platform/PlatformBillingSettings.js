import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const PlatformBillingSettings = () => {
    const dispatch = useDispatch();
    const [country, setCountry] = useState(""); // "" = global
    const [countries, setCountries] = useState([]);
    const [form, setForm] = useState({
        long_term_discount_percent: 0,
        long_term_discount_min_months: 12,
        addon_shop_rate: 0,
        addon_user_rate: 0,
        addon_product_rate: 0,
        fbr_monthly_price: 0,
        fbr_yearly_price: 0,
    });
    const [saving, setSaving] = useState(false);

    // Payment methods (request vs online checkout) + per-country channels.
    const [methods, setMethods] = useState({ request_enabled: true, checkout_enabled: false, channels: [] });
    const [savingMethods, setSavingMethods] = useState(false);

    const load = (c) => {
        apiConfig
            .get(apiBaseURL.PLATFORM_SETTINGS, { params: c ? { country: c } : {} })
            .then((res) => setForm((f) => ({ ...f, ...(res.data?.data || {}) })))
            .catch(() => {});
    };

    const loadMethods = (c) => {
        apiConfig
            .get(apiBaseURL.PLATFORM_BILLING_METHODS, { params: c ? { country: c } : {} })
            .then((res) => {
                const d = res.data?.data || {};
                setMethods({
                    request_enabled: !!d.request_enabled,
                    checkout_enabled: !!d.checkout_enabled,
                    channels: Array.isArray(d.channels) ? d.channels : [],
                });
            })
            .catch(() => {});
    };

    useEffect(() => {
        apiConfig
            .get(apiBaseURL.PUBLIC_COUNTRIES)
            .then((res) => setCountries(res.data?.data || []))
            .catch(() => {});
        load("");
        loadMethods("");
    }, []);

    const onCountryChange = (e) => {
        const c = e.target.value;
        setCountry(c);
        load(c);
        loadMethods(c);
    };

    const setChannel = (i, k) => (e) =>
        setMethods((m) => {
            const channels = m.channels.slice();
            channels[i] = { ...channels[i], [k]: e.target.value };
            return { ...m, channels };
        });

    const addChannel = () =>
        setMethods((m) => ({
            ...m,
            channels: [...m.channels, { key: "", label: "", type: "bank", account: "", url: "", instructions: "" }],
        }));

    const removeChannel = (i) =>
        setMethods((m) => ({ ...m, channels: m.channels.filter((_, idx) => idx !== i) }));

    const saveMethods = () => {
        setSavingMethods(true);
        apiConfig
            .post(apiBaseURL.PLATFORM_BILLING_METHODS, {
                country: country || null,
                request_enabled: methods.request_enabled,
                checkout_enabled: methods.checkout_enabled,
                channels: methods.channels.filter((c) => c.key && c.label),
            })
            .then((res) => {
                const d = res.data?.data || {};
                setMethods({
                    request_enabled: !!d.request_enabled,
                    checkout_enabled: !!d.checkout_enabled,
                    channels: Array.isArray(d.channels) ? d.channels : [],
                });
                dispatch(addToast({ text: "Payment methods saved." }));
            })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to save", type: toastType.ERROR }))
            )
            .finally(() => setSavingMethods(false));
    };

    const setF = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }));

    const save = () => {
        setSaving(true);
        apiConfig
            .post(apiBaseURL.PLATFORM_SETTINGS, { ...form, country: country || null })
            .then((res) => {
                setForm((f) => ({ ...f, ...(res.data?.data || {}) }));
                dispatch(addToast({ text: country ? `Saved settings for ${country}.` : "Saved global settings." }));
            })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to save", type: toastType.ERROR }))
            )
            .finally(() => setSaving(false));
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Billing Settings" />

            <div className="card mb-4">
                <div className="card-body">
                    <label className="form-label">Scope</label>
                    <select
                        className="form-control"
                        style={{ maxWidth: 320 }}
                        value={country}
                        onChange={onCountryChange}
                    >
                        <option value="">Global (all countries)</option>
                        {countries.map((c) => (
                            <option key={c.id} value={c.short_code}>
                                {c.short_code} · {c.name}
                            </option>
                        ))}
                    </select>
                    <span className="text-muted fs-small d-block mt-1">
                        Country settings override the global values for tenants in that country.
                    </span>
                </div>
            </div>

            <div className="card mb-4">
                <div className="card-header"><h6 className="mb-0">Long-term discount</h6></div>
                <div className="card-body">
                    <div className="row">
                        <div className="col-md-4 mb-3">
                            <label className="form-label">Discount %</label>
                            <input type="number" min="0" max="100" step="0.01" className="form-control" value={form.long_term_discount_percent} onChange={setF("long_term_discount_percent")} />
                        </div>
                        <div className="col-md-4 mb-3">
                            <label className="form-label">Applies from (months)</label>
                            <input type="number" min="1" max="60" className="form-control" value={form.long_term_discount_min_months} onChange={setF("long_term_discount_min_months")} />
                        </div>
                    </div>
                </div>
            </div>

            <div className="card mb-4">
                <div className="card-header"><h6 className="mb-0">Add-on rates (per unit)</h6></div>
                <div className="card-body">
                    <div className="row">
                        <div className="col-md-4 mb-3">
                            <label className="form-label">Per extra shop</label>
                            <input type="number" min="0" step="0.01" className="form-control" value={form.addon_shop_rate} onChange={setF("addon_shop_rate")} />
                        </div>
                        <div className="col-md-4 mb-3">
                            <label className="form-label">Per extra user</label>
                            <input type="number" min="0" step="0.01" className="form-control" value={form.addon_user_rate} onChange={setF("addon_user_rate")} />
                        </div>
                        <div className="col-md-4 mb-3">
                            <label className="form-label">Per extra product</label>
                            <input type="number" min="0" step="0.01" className="form-control" value={form.addon_product_rate} onChange={setF("addon_product_rate")} />
                        </div>
                    </div>
                </div>
            </div>

            <div className="card mb-4">
                <div className="card-header"><h6 className="mb-0">FBR (Pakistan) price</h6></div>
                <div className="card-body">
                    <div className="row">
                        <div className="col-md-4 mb-3">
                            <label className="form-label">Per month</label>
                            <input type="number" min="0" step="0.01" className="form-control" value={form.fbr_monthly_price} onChange={setF("fbr_monthly_price")} />
                        </div>
                        <div className="col-md-4 mb-3">
                            <label className="form-label">Per year</label>
                            <input type="number" min="0" step="0.01" className="form-control" value={form.fbr_yearly_price} onChange={setF("fbr_yearly_price")} />
                        </div>
                    </div>
                    <p className="text-muted mb-0">
                        FBR is offered only to Pakistan tenants. Set the price under the
                        Global scope, or override it for the PK country scope.
                    </p>
                </div>
            </div>

            <button className="btn btn-primary" type="button" disabled={saving} onClick={save}>
                {saving ? "Saving…" : country ? `Save ${country} settings` : "Save global settings"}
            </button>

            <div className="card mb-4 mt-4">
                <div className="card-header"><h6 className="mb-0">Payment methods</h6></div>
                <div className="card-body">
                    <div className="form-check form-switch mb-2">
                        <input
                            className="form-check-input"
                            type="checkbox"
                            id="m_request"
                            checked={methods.request_enabled}
                            onChange={(e) => setMethods((m) => ({ ...m, request_enabled: e.target.checked }))}
                        />
                        <label className="form-check-label" htmlFor="m_request">
                            Request (manual proof upload) — tenant uploads a receipt; you confirm it.
                        </label>
                    </div>
                    <div className="form-check form-switch mb-3">
                        <input
                            className="form-check-input"
                            type="checkbox"
                            id="m_checkout"
                            checked={methods.checkout_enabled}
                            onChange={(e) => setMethods((m) => ({ ...m, checkout_enabled: e.target.checked }))}
                        />
                        <label className="form-check-label" htmlFor="m_checkout">
                            Online checkout — hosted payment link (global) or local wallets/banks (e.g. PK: JazzCash, Easypaisa, HBL, Meezan, UBL).
                        </label>
                    </div>

                    <hr />
                    <div className="d-flex align-items-center justify-content-between mb-2">
                        <h6 className="mb-0">
                            Checkout channels {country ? `for ${country}` : "(global)"}
                        </h6>
                        <button className="btn btn-sm btn-outline-primary" type="button" onClick={addChannel}>
                            + Add channel
                        </button>
                    </div>
                    <p className="text-muted fs-small">
                        Select a country scope above to configure local channels (e.g. set
                        PK to expose JazzCash/Easypaisa/bank accounts). Global scope shows the
                        hosted payment link to all other countries.
                    </p>

                    {methods.channels.length === 0 ? (
                        <div className="text-muted">No channels — defaults will be used.</div>
                    ) : (
                        methods.channels.map((ch, i) => (
                            <div className="row g-2 align-items-end mb-2 border-bottom pb-2" key={i}>
                                <div className="col-md-2">
                                    <label className="form-label fs-small">Key</label>
                                    <input className="form-control" value={ch.key || ""} onChange={setChannel(i, "key")} placeholder="jazzcash" />
                                </div>
                                <div className="col-md-2">
                                    <label className="form-label fs-small">Label</label>
                                    <input className="form-control" value={ch.label || ""} onChange={setChannel(i, "label")} placeholder="JazzCash" />
                                </div>
                                <div className="col-md-2">
                                    <label className="form-label fs-small">Type</label>
                                    <select className="form-control" value={ch.type || "bank"} onChange={setChannel(i, "type")}>
                                        <option value="link">link</option>
                                        <option value="wallet">wallet</option>
                                        <option value="bank">bank</option>
                                    </select>
                                </div>
                                <div className="col-md-2">
                                    <label className="form-label fs-small">Account / number</label>
                                    <input className="form-control" value={ch.account || ""} onChange={setChannel(i, "account")} />
                                </div>
                                <div className="col-md-3">
                                    <label className="form-label fs-small">{(ch.type === "link") ? "Pay URL" : "Instructions"}</label>
                                    <input
                                        className="form-control"
                                        value={ch.type === "link" ? (ch.url || "") : (ch.instructions || "")}
                                        onChange={setChannel(i, ch.type === "link" ? "url" : "instructions")}
                                    />
                                </div>
                                <div className="col-md-1">
                                    <button className="btn btn-sm btn-outline-danger" type="button" onClick={() => removeChannel(i)}>✕</button>
                                </div>
                            </div>
                        ))
                    )}

                    <button className="btn btn-primary mt-2" type="button" disabled={savingMethods} onClick={saveMethods}>
                        {savingMethods ? "Saving…" : "Save payment methods"}
                    </button>
                </div>
            </div>
        </MasterLayout>
    );
};

export default PlatformBillingSettings;
