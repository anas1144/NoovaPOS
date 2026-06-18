import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const today = () => new Date().toISOString().slice(0, 10);

const WaterSupply = () => {
    const dispatch = useDispatch();
    const [tab, setTab] = useState("routes");
    const [routes, setRoutes] = useState([]);
    const [customers, setCustomers] = useState([]);
    const [bottles, setBottles] = useState([]);
    const [balances, setBalances] = useState([]);
    const [deposits, setDeposits] = useState([]);

    const [routeForm, setRouteForm] = useState({ name: "", area: "", driver_user_id: "" });
    const [bottleForm, setBottleForm] = useState({ customer_id: "", type: "issue", quantity: "1", date: today(), note: "" });
    const [depForm, setDepForm] = useState({ customer_id: "", amount: "", bottles: "", date: today(), note: "" });

    const toast = (text, type) => dispatch(addToast({ text, type }));
    const arr = (r) => r.data?.data?.data || r.data?.data || [];

    const loadRoutes = () => apiConfig.get(apiBaseURL.WATER_ROUTES).then((r) => setRoutes(r.data?.data || [])).catch(() => {});
    const loadCustomers = () => apiConfig.get(apiBaseURL.CUSTOMERS_LIST, { params: { page_size: 0 } }).then((r) => setCustomers(arr(r))).catch(() => {});
    const loadBottles = () => { apiConfig.get(apiBaseURL.WATER_BOTTLES).then((r) => setBottles(arr(r))).catch(() => {}); apiConfig.get(apiBaseURL.WATER_BOTTLE_BALANCES).then((r) => setBalances(r.data?.data || [])).catch(() => {}); };
    const loadDeposits = () => apiConfig.get(apiBaseURL.WATER_DEPOSITS).then((r) => setDeposits(arr(r))).catch(() => {});

    useEffect(() => { loadRoutes(); loadCustomers(); loadBottles(); loadDeposits(); }, []);

    const cName = (c) => c.name || c.attributes?.name || c.first_name || `#${c.id}`;
    const custName = (id) => { const c = customers.find((x) => String(x.id) === String(id)); return c ? cName(c) : id; };

    const addRoute = () => {
        if (!routeForm.name) { toast("Route name required", toastType.ERROR); return; }
        apiConfig.post(apiBaseURL.WATER_ROUTES, routeForm)
            .then(() => { toast("Route added."); setRouteForm({ name: "", area: "", driver_user_id: "" }); loadRoutes(); })
            .catch(({ response }) => toast(response?.data?.message || "Failed", toastType.ERROR));
    };
    const delRoute = (r) => apiConfig.delete(`${apiBaseURL.WATER_ROUTES}/${r.id}`).then(() => { toast("Removed."); loadRoutes(); }).catch(() => {});

    const addBottle = () => {
        if (!bottleForm.customer_id) { toast("Select a customer", toastType.ERROR); return; }
        apiConfig.post(apiBaseURL.WATER_BOTTLES, bottleForm)
            .then(() => { toast("Recorded."); setBottleForm((f) => ({ ...f, quantity: "1", note: "" })); loadBottles(); })
            .catch(({ response }) => toast(response?.data?.message || "Failed", toastType.ERROR));
    };

    const addDeposit = () => {
        if (!depForm.customer_id || depForm.amount === "") { toast("Customer + amount required", toastType.ERROR); return; }
        apiConfig.post(apiBaseURL.WATER_DEPOSITS, depForm)
            .then(() => { toast("Deposit recorded."); setDepForm((f) => ({ ...f, amount: "", bottles: "", note: "" })); loadDeposits(); })
            .catch(({ response }) => toast(response?.data?.message || "Failed", toastType.ERROR));
    };
    const refund = (d) => apiConfig.post(`${apiBaseURL.WATER_DEPOSITS}/${d.id}/refund`, {}).then(() => { toast("Refunded."); loadDeposits(); }).catch(({ response }) => toast(response?.data?.message || "Failed", toastType.ERROR));

    const TABS = [{ key: "routes", label: "Routes" }, { key: "bottles", label: "Bottle Ledger" }, { key: "deposits", label: "Deposits" }];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Water Supply" />

            <ul className="nav nav-pills mb-3">
                {TABS.map((t) => (
                    <li className="nav-item" key={t.key}>
                        <button className={`nav-link ${tab === t.key ? "active" : ""}`} onClick={() => setTab(t.key)}>{t.label}</button>
                    </li>
                ))}
            </ul>

            {tab === "routes" && (
                <div className="card"><div className="card-body">
                    <div className="d-flex flex-wrap gap-2 align-items-end mb-3">
                        <div><label className="form-label">Route name</label>
                            <input className="form-control" value={routeForm.name} onChange={(e) => setRouteForm((f) => ({ ...f, name: e.target.value }))} /></div>
                        <div><label className="form-label">Area</label>
                            <input className="form-control" value={routeForm.area} onChange={(e) => setRouteForm((f) => ({ ...f, area: e.target.value }))} /></div>
                        <Button variant="primary" onClick={addRoute}>Add route</Button>
                    </div>
                    <table className="table align-middle">
                        <thead><tr><th>Name</th><th>Area</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            {routes.length === 0 ? <tr><td colSpan={4} className="text-center text-muted py-3">No routes.</td></tr>
                                : routes.map((r) => (<tr key={r.id}><td>{r.name}</td><td>{r.area || "—"}</td><td>{r.status ? "Active" : "Off"}</td>
                                    <td className="text-end"><button className="btn btn-sm btn-link text-danger" onClick={() => delRoute(r)}>Delete</button></td></tr>))}
                        </tbody>
                    </table>
                </div></div>
            )}

            {tab === "bottles" && (
                <div className="row">
                    <div className="col-lg-7 mb-3"><div className="card h-100"><div className="card-header"><h6 className="mb-0">Bottle ledger</h6></div>
                        <div className="card-body">
                            <div className="d-flex flex-wrap gap-2 align-items-end mb-3">
                                <div><label className="form-label">Customer</label>
                                    <select className="form-control" value={bottleForm.customer_id} onChange={(e) => setBottleForm((f) => ({ ...f, customer_id: e.target.value }))}>
                                        <option value="">—</option>{customers.map((c) => <option key={c.id} value={c.id}>{cName(c)}</option>)}
                                    </select></div>
                                <div><label className="form-label">Type</label>
                                    <select className="form-control" value={bottleForm.type} onChange={(e) => setBottleForm((f) => ({ ...f, type: e.target.value }))}>
                                        <option value="issue">Issue</option><option value="return">Return</option>
                                    </select></div>
                                <div><label className="form-label">Qty</label>
                                    <input type="number" className="form-control" style={{ width: 90 }} value={bottleForm.quantity} onChange={(e) => setBottleForm((f) => ({ ...f, quantity: e.target.value }))} /></div>
                                <Button variant="primary" onClick={addBottle}>Record</Button>
                            </div>
                            <table className="table table-sm align-middle">
                                <thead><tr><th>Date</th><th>Customer</th><th>Type</th><th>Qty</th></tr></thead>
                                <tbody>
                                    {bottles.length === 0 ? <tr><td colSpan={4} className="text-center text-muted py-3">No movements.</td></tr>
                                        : bottles.map((b) => (<tr key={b.id}><td>{b.date ? new Date(b.date).toLocaleDateString() : "—"}</td><td>{custName(b.customer_id)}</td>
                                            <td className="text-capitalize">{b.type}</td><td>{b.quantity}</td></tr>))}
                                </tbody>
                            </table>
                        </div></div></div>
                    <div className="col-lg-5 mb-3"><div className="card h-100"><div className="card-header"><h6 className="mb-0">Outstanding balances</h6></div>
                        <div className="card-body">
                            {balances.length === 0 ? <div className="text-muted">All settled.</div> : (
                                <table className="table table-sm align-middle">
                                    <thead><tr><th>Customer</th><th className="text-end">Bottles out</th></tr></thead>
                                    <tbody>{balances.map((b) => (<tr key={b.customer_id}><td>{custName(b.customer_id)}</td><td className="text-end fw-semibold">{b.balance}</td></tr>))}</tbody>
                                </table>
                            )}
                        </div></div></div>
                </div>
            )}

            {tab === "deposits" && (
                <div className="card"><div className="card-body">
                    <div className="d-flex flex-wrap gap-2 align-items-end mb-3">
                        <div><label className="form-label">Customer</label>
                            <select className="form-control" value={depForm.customer_id} onChange={(e) => setDepForm((f) => ({ ...f, customer_id: e.target.value }))}>
                                <option value="">—</option>{customers.map((c) => <option key={c.id} value={c.id}>{cName(c)}</option>)}
                            </select></div>
                        <div><label className="form-label">Amount</label>
                            <input type="number" className="form-control" style={{ width: 120 }} value={depForm.amount} onChange={(e) => setDepForm((f) => ({ ...f, amount: e.target.value }))} /></div>
                        <div><label className="form-label">Bottles</label>
                            <input type="number" className="form-control" style={{ width: 100 }} value={depForm.bottles} onChange={(e) => setDepForm((f) => ({ ...f, bottles: e.target.value }))} /></div>
                        <Button variant="primary" onClick={addDeposit}>Add deposit</Button>
                    </div>
                    <table className="table align-middle">
                        <thead><tr><th>Customer</th><th>Amount</th><th>Bottles</th><th>Status</th><th>Date</th><th></th></tr></thead>
                        <tbody>
                            {deposits.length === 0 ? <tr><td colSpan={6} className="text-center text-muted py-3">No deposits.</td></tr>
                                : deposits.map((d) => (<tr key={d.id}><td>{custName(d.customer_id)}</td><td>{d.amount}</td><td>{d.bottles}</td>
                                    <td><span className={`badge ${d.status === "refunded" ? "bg-light-secondary" : "bg-light-success"}`}>{d.status}</span></td>
                                    <td>{d.date ? new Date(d.date).toLocaleDateString() : "—"}</td>
                                    <td className="text-end">{d.status !== "refunded" && <button className="btn btn-sm btn-link" onClick={() => refund(d)}>Refund</button>}</td></tr>))}
                        </tbody>
                    </table>
                </div></div>
            )}
        </MasterLayout>
    );
};

export default WaterSupply;
