import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const fmtDate = (d) => (d ? new Date(d).toLocaleDateString() : "—");

const StatusBadge = ({ status, isExpired }) => {
    const map = {
        active: "bg-light-success",
        trialing: "bg-light-primary",
        past_due: "bg-light-warning",
        suspended: "bg-light-danger",
        canceled: "bg-light-danger",
        expired: "bg-light-danger",
    };
    const cls = isExpired ? "bg-light-danger" : map[status] || "bg-light-secondary";
    return (
        <span className={`badge ${cls} text-capitalize`}>
            {isExpired ? "expired" : (status || "none").replace(/_/g, " ")}
        </span>
    );
};

const UsageCard = ({ label, used, limit }) => {
    const unlimited = limit === null || limit === undefined;
    const pct = unlimited || !limit ? 0 : Math.min(100, Math.round((used / limit) * 100));
    const over = !unlimited && used > limit;
    return (
        <div className="col-md-4 col-sm-6 mb-3">
            <div className="card h-100">
                <div className="card-body py-3">
                    <div className="d-flex justify-content-between">
                        <span className="text-muted text-capitalize">{label}</span>
                        <span className={`fw-bold ${over ? "text-danger" : ""}`}>
                            {used}
                            {unlimited ? "" : ` / ${limit}`}
                        </span>
                    </div>
                    {!unlimited && (
                        <div className="progress mt-2" style={{ height: 6 }}>
                            <div
                                className={`progress-bar ${over ? "bg-danger" : "bg-primary"}`}
                                style={{ width: `${pct}%` }}
                            />
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

const Billing = () => {
    const dispatch = useDispatch();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [form, setForm] = useState({ plan_id: "", billing_cycle: "monthly", periods: 1, reference: "" });
    const [proof, setProof] = useState(null);
    const [submitting, setSubmitting] = useState(false);
    // Add-ons bought together with the plan (separate payment).
    const [planAddons, setPlanAddons] = useState({ shops: 0, users: 0, products: 0 });
    const [addon, setAddon] = useState({ shops: 0, users: 0, products: 0 });
    const [addonProof, setAddonProof] = useState(null);
    const [addonSubmitting, setAddonSubmitting] = useState(false);
    const [fbr, setFbr] = useState({ billing_cycle: "monthly", periods: 1 });
    const [fbrProof, setFbrProof] = useState(null);
    const [fbrSubmitting, setFbrSubmitting] = useState(false);
    // Payment method: 'request' (upload proof) or 'checkout' (online link / local wallet-bank).
    const [payMethod, setPayMethod] = useState("request");
    const [checkoutChannel, setCheckoutChannel] = useState("");

    const load = () => {
        setLoading(true);
        apiConfig
            .get(apiBaseURL.BILLING_OVERVIEW)
            .then((res) => setData(res.data?.data || null))
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to load billing", type: toastType.ERROR }))
            )
            .finally(() => setLoading(false));
    };

    useEffect(() => { load(); }, []);

    // Default the method/channel once overview loads (prefer checkout when it's
    // the only enabled method).
    const methodsCfg = data?.methods || { request: true, checkout: false };
    const checkoutChannels = data?.checkout_channels || [];
    useEffect(() => {
        if (!data) return;
        if (!methodsCfg.request && methodsCfg.checkout) setPayMethod("checkout");
        else setPayMethod("request");
        if (!checkoutChannel && checkoutChannels.length) setCheckoutChannel(checkoutChannels[0].key);
    }, [data]);

    const plans = data?.plans || [];
    const selectedPlan = plans.find((p) => String(p.id) === String(form.plan_id));
    const unitPrice = selectedPlan
        ? form.billing_cycle === "yearly"
            ? Number(selectedPlan.price_yearly || (selectedPlan.price || 0) * 12)
            : Number(selectedPlan.price || 0)
        : 0;
    const periodsNum = Math.max(1, Number(form.periods) || 1);
    const totalMonths = form.billing_cycle === "yearly" ? periodsNum * 12 : periodsNum;
    const discount = data?.discount || { percent: 0, min_months: 12 };
    const discountApplies =
        Number(discount.percent) > 0 && totalMonths >= Number(discount.min_months || 12);
    const gross = unitPrice * periodsNum;
    const total = discountApplies ? gross * (1 - Number(discount.percent) / 100) : gross;

    const addonRates = data?.addon_rates || { shop: 0, user: 0, product: 0 };
    const addonTotal =
        (Number(addon.shops) || 0) * Number(addonRates.shop || 0) +
        (Number(addon.users) || 0) * Number(addonRates.user || 0) +
        (Number(addon.products) || 0) * Number(addonRates.product || 0);

    const submitAddon = () => {
        if ((Number(addon.shops) || 0) + (Number(addon.users) || 0) + (Number(addon.products) || 0) <= 0) {
            dispatch(addToast({ text: "Select at least one add-on quantity", type: toastType.ERROR }));
            return;
        }
        if (!addonProof) {
            dispatch(addToast({ text: "Please attach your payment proof", type: toastType.ERROR }));
            return;
        }
        setAddonSubmitting(true);
        const fd = new FormData();
        fd.append("shops", Number(addon.shops) || 0);
        fd.append("users", Number(addon.users) || 0);
        fd.append("products", Number(addon.products) || 0);
        fd.append("method", "bank_transfer");
        fd.append("proof", addonProof);
        apiConfig
            .post(apiBaseURL.BILLING_ADDON_REQUEST, fd)
            .then((res) => {
                setAddon({ shops: 0, users: 0, products: 0 });
                setAddonProof(null);
                dispatch(addToast({ text: res.data?.message || "Add-on request submitted" }));
                load();
            })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to submit add-on", type: toastType.ERROR }))
            )
            .finally(() => setAddonSubmitting(false));
    };

    const fbrInfo = data?.fbr || { eligible: false };
    const fbrUnit =
        fbr.billing_cycle === "yearly"
            ? Number(fbrInfo.yearly_price || 0)
            : Number(fbrInfo.monthly_price || 0);
    const fbrTotal = fbrUnit * Math.max(1, Number(fbr.periods) || 1);

    const submitFbr = () => {
        if (!fbrProof) {
            dispatch(addToast({ text: "Please attach your payment proof", type: toastType.ERROR }));
            return;
        }
        setFbrSubmitting(true);
        const fd = new FormData();
        fd.append("billing_cycle", fbr.billing_cycle);
        fd.append("periods", Number(fbr.periods) || 1);
        fd.append("method", "bank_transfer");
        fd.append("proof", fbrProof);
        apiConfig
            .post(apiBaseURL.BILLING_FBR_REQUEST, fd)
            .then((res) => {
                setFbrProof(null);
                dispatch(addToast({ text: res.data?.message || "FBR request submitted" }));
                load();
            })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to submit FBR request", type: toastType.ERROR }))
            )
            .finally(() => setFbrSubmitting(false));
    };

    const submit = () => {
        if (!form.plan_id) {
            dispatch(addToast({ text: "Please choose a plan", type: toastType.ERROR }));
            return;
        }
        if (!proof) {
            dispatch(addToast({ text: "Please attach your payment proof (screenshot/receipt)", type: toastType.ERROR }));
            return;
        }
        setSubmitting(true);
        const fd = new FormData();
        fd.append("plan_id", form.plan_id);
        fd.append("billing_cycle", form.billing_cycle);
        fd.append("periods", Number(form.periods) || 1);
        fd.append("method", "bank_transfer");
        if (form.reference) fd.append("reference", form.reference);
        fd.append("proof", proof);
        // Optional add-ons in the same checkout (recorded as a separate payment).
        fd.append("addon_shops", Number(planAddons.shops) || 0);
        fd.append("addon_users", Number(planAddons.users) || 0);
        fd.append("addon_products", Number(planAddons.products) || 0);
        apiConfig
            .post(apiBaseURL.BILLING_REQUEST, fd)
            .then((res) => {
                setProof(null);
                dispatch(addToast({ text: res.data?.message || "Request submitted" }));
                load();
            })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to submit request", type: toastType.ERROR }))
            )
            .finally(() => setSubmitting(false));
    };

    const submitCheckout = () => {
        if (!form.plan_id) {
            dispatch(addToast({ text: "Please choose a plan", type: toastType.ERROR }));
            return;
        }
        if (!checkoutChannel) {
            dispatch(addToast({ text: "Please choose a payment channel", type: toastType.ERROR }));
            return;
        }
        setSubmitting(true);
        const fd = new FormData();
        fd.append("plan_id", form.plan_id);
        fd.append("billing_cycle", form.billing_cycle);
        fd.append("periods", Number(form.periods) || 1);
        fd.append("channel", checkoutChannel);
        if (form.reference) fd.append("reference", form.reference);
        if (proof) fd.append("proof", proof);
        fd.append("addon_shops", Number(planAddons.shops) || 0);
        fd.append("addon_users", Number(planAddons.users) || 0);
        fd.append("addon_products", Number(planAddons.products) || 0);
        apiConfig
            .post(apiBaseURL.BILLING_CHECKOUT, fd)
            .then((res) => {
                const url = res.data?.data?.checkout_url;
                setProof(null);
                dispatch(addToast({ text: res.data?.message || "Checkout created" }));
                if (url) window.open(url, "_blank", "noopener");
                load();
            })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to create checkout", type: toastType.ERROR }))
            )
            .finally(() => setSubmitting(false));
    };

    const sub = data?.subscription;
    const pending = data?.pending_payment;
    const selectedChannel = checkoutChannels.find((c) => c.key === checkoutChannel);

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Billing & Subscription" />

            {loading ? (
                <div className="text-muted">Loading…</div>
            ) : data?.is_platform_user ? (
                <div className="alert alert-light-primary">
                    Billing is managed per tenant. As a platform user you manage plans and
                    payments under the Platform menu.
                </div>
            ) : (
                <>
                    {/* Current subscription */}
                    <div className="card mb-4">
                        <div className="card-body">
                            <div className="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div>
                                    <h5 className="mb-1">
                                        Current Plan:{" "}
                                        {sub?.plan?.name || "No active plan"}
                                    </h5>
                                    <div className="d-flex align-items-center gap-2">
                                        <StatusBadge status={sub?.status} isExpired={sub?.is_expired} />
                                        {sub?.status === "trialing" && sub?.trial_days_left != null && (
                                            <span className="text-muted">
                                                Trial ends in {sub.trial_days_left} day(s) ({fmtDate(sub.trial_ends_at)})
                                            </span>
                                        )}
                                        {sub?.ends_at && (
                                            <span className="text-muted">Valid until {fmtDate(sub.ends_at)}</span>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {sub?.is_expired && (
                                <div className="alert alert-light-danger mt-3 mb-0">
                                    Your subscription has ended. The app is locked until you renew below.
                                </div>
                            )}
                            {pending && (
                                <div className="alert alert-light-warning mt-3 mb-0">
                                    A payment request is pending confirmation:{" "}
                                    <strong>{pending.amount} {pending.currency}</strong> ·{" "}
                                    {pending.periods} {pending.billing_cycle} period(s). The platform
                                    admin will activate it once payment is confirmed.
                                </div>
                            )}
                            {(data?.rejected_payments || []).length > 0 && (
                                <div className="alert alert-light-danger mt-3 mb-0">
                                    <strong>Payment rejected.</strong> The following payment(s) were
                                    not accepted — please re-submit:
                                    <ul className="mb-0 mt-1">
                                        {data.rejected_payments.map((rp) => (
                                            <li key={rp.id}>
                                                {rp.type === "addon" ? "Add-on" : rp.type === "fbr" ? "FBR" : "Subscription"} ·{" "}
                                                {rp.amount} {rp.currency}
                                                {rp.note ? ` — ${rp.note}` : ""}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Usage vs limits */}
                    <h6 className="mb-2">Usage</h6>
                    <div className="row mb-4">
                        {["stores", "shops", "registers", "users", "products"].map((k) => (
                            <UsageCard
                                key={k}
                                label={k === "registers" ? "registers (this month)" : k}
                                used={data?.usage?.[k] ?? 0}
                                limit={data?.limits?.[k]}
                            />
                        ))}
                    </div>

                    {/* Where to pay — bank accounts for the tenant's country */}
                    {(data?.bank_accounts || []).length > 0 && (
                        <div className="card mb-4">
                            <div className="card-header">
                                <h6 className="mb-0">Where to pay</h6>
                            </div>
                            <div className="card-body">
                                <div className="row">
                                    {data.bank_accounts.map((b) => (
                                        <div className="col-md-6 mb-3" key={b.id}>
                                            <div className="border rounded p-3 h-100">
                                                <div className="fw-bold">
                                                    {b.bank_name}
                                                    {b.currency ? ` · ${b.currency}` : ""}
                                                </div>
                                                <div className="text-muted small">
                                                    Title: {b.account_title}
                                                </div>
                                                {b.account_number && (
                                                    <div className="text-muted small">
                                                        Account #: {b.account_number}
                                                    </div>
                                                )}
                                                {b.iban && (
                                                    <div className="text-muted small">IBAN: {b.iban}</div>
                                                )}
                                                {b.swift && (
                                                    <div className="text-muted small">SWIFT: {b.swift}</div>
                                                )}
                                                {b.instructions && (
                                                    <div className="small mt-1">{b.instructions}</div>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <p className="text-muted mb-0">
                                    Transfer the total to one of the accounts above, then
                                    submit the request below with your payment proof.
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Buy / upgrade */}
                    <div className="card">
                        <div className="card-header">
                            <h6 className="mb-0">Upgrade / Renew</h6>
                        </div>
                        <div className="card-body">
                            <div className="row align-items-end">
                                <div className="col-md-4 mb-3">
                                    <label className="form-label">Plan</label>
                                    <select
                                        className="form-control"
                                        value={form.plan_id}
                                        onChange={(e) => setForm((f) => ({ ...f, plan_id: e.target.value }))}
                                    >
                                        <option value="">-- Select plan --</option>
                                        {plans.map((p) => (
                                            <option key={p.id} value={p.id}>{p.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="col-md-3 mb-3">
                                    <label className="form-label">Billing Cycle</label>
                                    <select
                                        className="form-control"
                                        value={form.billing_cycle}
                                        onChange={(e) => setForm((f) => ({ ...f, billing_cycle: e.target.value }))}
                                    >
                                        <option value="monthly">Monthly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                                <div className="col-md-2 mb-3">
                                    <label className="form-label">
                                        {form.billing_cycle === "yearly" ? "Years" : "Months"}
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        max="60"
                                        className="form-control"
                                        value={form.periods}
                                        onChange={(e) => setForm((f) => ({ ...f, periods: e.target.value }))}
                                    />
                                </div>
                                <div className="col-md-3 mb-3">
                                    <label className="form-label">Total</label>
                                    <div className="form-control bg-light fw-bold text-dark">
                                        {total.toFixed(2)} {selectedPlan?.currency || ""}
                                    </div>
                                    {discountApplies && (
                                        <span className="badge bg-light-success mt-1">
                                            {discount.percent}% long-term discount applied
                                        </span>
                                    )}
                                    {!discountApplies && Number(discount.percent) > 0 && (
                                        <span className="text-muted small d-block mt-1">
                                            Buy {discount.min_months}+ months for {discount.percent}% off
                                        </span>
                                    )}
                                </div>
                                <div className="col-md-8 mb-3">
                                    <label className="form-label">Payment reference (bank transfer / txn id)</label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        placeholder="Optional — helps the admin match your payment"
                                        value={form.reference}
                                        onChange={(e) => setForm((f) => ({ ...f, reference: e.target.value }))}
                                    />
                                </div>
                                {(Number(addonRates.shop) > 0 || Number(addonRates.user) > 0 || Number(addonRates.product) > 0) && (
                                    <div className="col-md-12 mb-3">
                                        <label className="form-label">Add extra capacity with this plan (optional)</label>
                                        <div className="row">
                                            <div className="col-md-4">
                                                <input type="number" min="0" className="form-control" placeholder="Extra shops"
                                                    value={planAddons.shops}
                                                    onChange={(e) => setPlanAddons((a) => ({ ...a, shops: e.target.value }))} />
                                            </div>
                                            <div className="col-md-4">
                                                <input type="number" min="0" className="form-control" placeholder="Extra users"
                                                    value={planAddons.users}
                                                    onChange={(e) => setPlanAddons((a) => ({ ...a, users: e.target.value }))} />
                                            </div>
                                            <div className="col-md-4">
                                                <input type="number" min="0" className="form-control" placeholder="Extra products"
                                                    value={planAddons.products}
                                                    onChange={(e) => setPlanAddons((a) => ({ ...a, products: e.target.value }))} />
                                            </div>
                                        </div>
                                        <span className="text-muted fs-small">
                                            Charged as a separate add-on payment, confirmed independently.
                                        </span>
                                    </div>
                                )}
                                {/* Method selector — only when both methods are enabled */}
                                {methodsCfg.request && methodsCfg.checkout && (
                                    <div className="col-md-12 mb-3">
                                        <label className="form-label d-block">Payment method</label>
                                        <div className="btn-group" role="group">
                                            <button
                                                type="button"
                                                className={`btn btn-sm ${payMethod === "request" ? "btn-primary" : "btn-outline-primary"}`}
                                                onClick={() => setPayMethod("request")}
                                            >
                                                Upload proof
                                            </button>
                                            <button
                                                type="button"
                                                className={`btn btn-sm ${payMethod === "checkout" ? "btn-primary" : "btn-outline-primary"}`}
                                                onClick={() => setPayMethod("checkout")}
                                            >
                                                Pay online
                                            </button>
                                        </div>
                                    </div>
                                )}

                                {/* CHECKOUT: pick a channel (global link / PK wallet-bank) */}
                                {payMethod === "checkout" && methodsCfg.checkout ? (
                                    <>
                                        <div className="col-md-5 mb-3">
                                            <label className="form-label">Payment channel</label>
                                            <select
                                                className="form-control"
                                                value={checkoutChannel}
                                                onChange={(e) => setCheckoutChannel(e.target.value)}
                                            >
                                                {checkoutChannels.length === 0 && <option value="">No channels available</option>}
                                                {checkoutChannels.map((c) => (
                                                    <option key={c.key} value={c.key}>{c.label}</option>
                                                ))}
                                            </select>
                                            {selectedChannel?.account && (
                                                <span className="text-muted small d-block mt-1">
                                                    Pay to: <strong>{selectedChannel.account}</strong>
                                                </span>
                                            )}
                                            {selectedChannel?.instructions && (
                                                <span className="text-muted small d-block">{selectedChannel.instructions}</span>
                                            )}
                                        </div>
                                        <div className="col-md-3 mb-3">
                                            <label className="form-label">Proof (optional)</label>
                                            <input
                                                type="file"
                                                accept="image/png,image/jpeg,image/jpg,image/webp"
                                                className="form-control"
                                                onChange={(e) => setProof(e.target.files?.[0] || null)}
                                            />
                                        </div>
                                        <div className="col-md-4 mb-3 text-md-end align-self-end">
                                            <button
                                                className="btn btn-primary"
                                                type="button"
                                                disabled={submitting}
                                                onClick={submitCheckout}
                                            >
                                                {submitting ? "Processing…" : (selectedChannel?.type === "link" ? "Continue to pay" : "Pay & submit")}
                                            </button>
                                        </div>
                                    </>
                                ) : (
                                    <>
                                        <div className="col-md-8 mb-3">
                                            <label className="form-label">
                                                Payment proof (screenshot / receipt)
                                                <span className="text-danger"> *</span>
                                            </label>
                                            <input
                                                type="file"
                                                accept="image/png,image/jpeg,image/jpg,image/webp"
                                                className="form-control"
                                                onChange={(e) => setProof(e.target.files?.[0] || null)}
                                            />
                                            {proof && (
                                                <span className="text-muted small">{proof.name}</span>
                                            )}
                                        </div>
                                        <div className="col-md-4 mb-3 text-md-end align-self-end">
                                            <button
                                                className="btn btn-primary"
                                                type="button"
                                                disabled={submitting}
                                                onClick={submit}
                                            >
                                                {submitting ? "Submitting…" : "Submit payment request"}
                                            </button>
                                        </div>
                                    </>
                                )}
                            </div>
                            <p className="text-muted mb-0">
                                {payMethod === "checkout"
                                    ? "Online checkout records your payment against the selected channel. Your subscription activates once the platform admin confirms it."
                                    : "Submitting creates a payment request. Your subscription activates once the platform admin confirms the payment."}
                            </p>
                        </div>
                    </div>

                    {/* Add-ons — buy extra capacity */}
                    {(Number(addonRates.shop) > 0 ||
                        Number(addonRates.user) > 0 ||
                        Number(addonRates.product) > 0) && (
                        <div className="card mt-4">
                            <div className="card-header">
                                <h6 className="mb-0">Add-ons (extra capacity)</h6>
                            </div>
                            <div className="card-body">
                                <div className="row align-items-end">
                                    <div className="col-md-4 mb-3">
                                        <label className="form-label">
                                            Extra shops
                                            <span className="text-muted"> ({addonRates.shop}/ea)</span>
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            className="form-control"
                                            value={addon.shops}
                                            onChange={(e) => setAddon((a) => ({ ...a, shops: e.target.value }))}
                                        />
                                    </div>
                                    <div className="col-md-4 mb-3">
                                        <label className="form-label">
                                            Extra users
                                            <span className="text-muted"> ({addonRates.user}/ea)</span>
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            className="form-control"
                                            value={addon.users}
                                            onChange={(e) => setAddon((a) => ({ ...a, users: e.target.value }))}
                                        />
                                    </div>
                                    <div className="col-md-4 mb-3">
                                        <label className="form-label">
                                            Extra products
                                            <span className="text-muted"> ({addonRates.product}/ea)</span>
                                        </label>
                                        <input
                                            type="number"
                                            min="0"
                                            className="form-control"
                                            value={addon.products}
                                            onChange={(e) => setAddon((a) => ({ ...a, products: e.target.value }))}
                                        />
                                    </div>
                                    <div className="col-md-4 mb-3">
                                        <label className="form-label">Add-on total</label>
                                        <div className="form-control bg-light fw-bold text-dark">
                                            {addonTotal.toFixed(2)}
                                        </div>
                                    </div>
                                    <div className="col-md-5 mb-3">
                                        <label className="form-label">
                                            Payment proof<span className="text-danger"> *</span>
                                        </label>
                                        <input
                                            type="file"
                                            accept="image/png,image/jpeg,image/jpg,image/webp"
                                            className="form-control"
                                            onChange={(e) => setAddonProof(e.target.files?.[0] || null)}
                                        />
                                    </div>
                                    <div className="col-md-3 mb-3 text-md-end align-self-end">
                                        <button
                                            className="btn btn-primary"
                                            type="button"
                                            disabled={addonSubmitting}
                                            onClick={submitAddon}
                                        >
                                            {addonSubmitting ? "Submitting…" : "Buy add-ons"}
                                        </button>
                                    </div>
                                </div>
                                <p className="text-muted mb-0">
                                    Current extras: {data?.extras?.shops || 0} shops,{" "}
                                    {data?.extras?.users || 0} users, {data?.extras?.products || 0} products.
                                    Approved add-ons raise your plan limits.
                                </p>
                            </div>
                        </div>
                    )}

                    {/* FBR (Pakistan only) */}
                    {fbrInfo.eligible && (
                        <div className="card mt-4">
                            <div className="card-header d-flex justify-content-between align-items-center">
                                <h6 className="mb-0">FBR Integration (Pakistan)</h6>
                                {fbrInfo.status?.enabled ? (
                                    <span className="badge bg-light-success">
                                        Active{fbrInfo.status?.expires_at ? ` · until ${fmtDate(fbrInfo.status.expires_at)}` : ""}
                                    </span>
                                ) : (
                                    <span className="badge bg-light-secondary">Not enabled</span>
                                )}
                            </div>
                            <div className="card-body">
                                {fbrUnit <= 0 ? (
                                    <div className="text-muted">
                                        FBR pricing isn't configured yet. Please check back later.
                                    </div>
                                ) : (
                                    <div className="row align-items-end">
                                        <div className="col-md-3 mb-3">
                                            <label className="form-label">Billing Cycle</label>
                                            <select
                                                className="form-control"
                                                value={fbr.billing_cycle}
                                                onChange={(e) => setFbr((f) => ({ ...f, billing_cycle: e.target.value }))}
                                            >
                                                <option value="monthly">Monthly</option>
                                                <option value="yearly">Yearly</option>
                                            </select>
                                        </div>
                                        <div className="col-md-2 mb-3">
                                            <label className="form-label">
                                                {fbr.billing_cycle === "yearly" ? "Years" : "Months"}
                                            </label>
                                            <input
                                                type="number"
                                                min="1"
                                                max="60"
                                                className="form-control"
                                                value={fbr.periods}
                                                onChange={(e) => setFbr((f) => ({ ...f, periods: e.target.value }))}
                                            />
                                        </div>
                                        <div className="col-md-3 mb-3">
                                            <label className="form-label">Total (PKR)</label>
                                            <div className="form-control bg-light fw-bold text-dark">
                                                {fbrTotal.toFixed(2)}
                                            </div>
                                        </div>
                                        <div className="col-md-4 mb-3">
                                            <label className="form-label">
                                                Payment proof<span className="text-danger"> *</span>
                                            </label>
                                            <input
                                                type="file"
                                                accept="image/png,image/jpeg,image/jpg,image/webp"
                                                className="form-control"
                                                onChange={(e) => setFbrProof(e.target.files?.[0] || null)}
                                            />
                                        </div>
                                        <div className="col-md-12 text-md-end">
                                            <button
                                                className="btn btn-primary"
                                                type="button"
                                                disabled={fbrSubmitting}
                                                onClick={submitFbr}
                                            >
                                                {fbrSubmitting
                                                    ? "Submitting…"
                                                    : fbrInfo.status?.enabled
                                                    ? "Extend FBR"
                                                    : "Enable FBR"}
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </>
            )}
        </MasterLayout>
    );
};

export default Billing;
