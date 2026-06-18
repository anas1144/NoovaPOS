import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Button, Modal } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const today = () => new Date().toISOString().slice(0, 10);
const emptyItem = () => ({ product_id: "", quantity: "" });

const BakeryProduction = () => {
    const dispatch = useDispatch();
    const [tab, setTab] = useState("recipes");
    const [recipes, setRecipes] = useState([]);
    const [runs, setRuns] = useState([]);
    const [products, setProducts] = useState([]);

    const [show, setShow] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ product_id: "", name: "", yield_qty: "1", items: [emptyItem()] });

    const [run, setRun] = useState({ recipe_id: "", date: today(), batches: "1", wastage_qty: "0", note: "" });

    const toast = (text, type) => dispatch(addToast({ text, type }));
    const arr = (r) => r.data?.data?.data || r.data?.data || [];

    const loadRecipes = () => apiConfig.get(apiBaseURL.BAKERY_RECIPES).then((r) => setRecipes(r.data?.data || [])).catch(() => {});
    const loadRuns = () => apiConfig.get(apiBaseURL.BAKERY_PRODUCTION).then((r) => setRuns(arr(r))).catch(() => {});
    const loadProducts = () => apiConfig.get(apiBaseURL.PRODUCTS_LIST, { params: { page_size: 0 } }).then((r) => setProducts(arr(r))).catch(() => {});
    useEffect(() => { loadRecipes(); loadRuns(); loadProducts(); }, []);

    const pName = (p) => `${p.name || p.attributes?.name || `#${p.id}`}`;
    const prodName = (id) => { const p = products.find((x) => String(x.id) === String(id)); return p ? pName(p) : id; };

    const openRecipe = (r) => {
        setEditing(r?.id || null);
        setForm(r ? {
            product_id: r.product_id, name: r.name, yield_qty: String(r.yield_qty),
            items: (r.items || []).map((it) => ({ product_id: it.product_id, quantity: String(it.quantity) })) || [emptyItem()],
        } : { product_id: "", name: "", yield_qty: "1", items: [emptyItem()] });
        setShow(true);
    };
    const setItem = (i, k) => (e) => setForm((f) => ({ ...f, items: f.items.map((it, idx) => idx === i ? { ...it, [k]: e.target.value } : it) }));
    const addItem = () => setForm((f) => ({ ...f, items: [...f.items, emptyItem()] }));
    const removeItem = (i) => setForm((f) => ({ ...f, items: f.items.length > 1 ? f.items.filter((_, idx) => idx !== i) : f.items }));

    const saveRecipe = () => {
        if (!form.product_id || !form.name) { toast("Finished product + name required", toastType.ERROR); return; }
        const body = { ...form, items: form.items.filter((it) => it.product_id && it.quantity !== "") };
        const req = editing ? apiConfig.patch(`${apiBaseURL.BAKERY_RECIPES}/${editing}`, body) : apiConfig.post(apiBaseURL.BAKERY_RECIPES, body);
        req.then(() => { toast("Recipe saved."); setShow(false); loadRecipes(); })
            .catch(({ response }) => toast(response?.data?.message || "Save failed", toastType.ERROR));
    };
    const delRecipe = (r) => apiConfig.delete(`${apiBaseURL.BAKERY_RECIPES}/${r.id}`).then(() => { toast("Removed."); loadRecipes(); }).catch(() => {});

    const recordRun = () => {
        if (!run.recipe_id) { toast("Select a recipe", toastType.ERROR); return; }
        apiConfig.post(apiBaseURL.BAKERY_PRODUCTION, run)
            .then(() => { toast("Production recorded."); setRun((f) => ({ ...f, batches: "1", wastage_qty: "0", note: "" })); loadRuns(); })
            .catch(({ response }) => toast(response?.data?.message || "Failed", toastType.ERROR));
    };

    const TABS = [{ key: "recipes", label: "Recipes" }, { key: "production", label: "Production" }];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Bakery Production" />

            <ul className="nav nav-pills mb-3">
                {TABS.map((t) => (
                    <li className="nav-item" key={t.key}>
                        <button className={`nav-link ${tab === t.key ? "active" : ""}`} onClick={() => setTab(t.key)}>{t.label}</button>
                    </li>
                ))}
            </ul>

            {tab === "recipes" && (
                <div className="card">
                    <div className="card-header d-flex justify-content-between align-items-center">
                        <h6 className="mb-0">Recipes</h6><Button variant="primary" onClick={() => openRecipe(null)}>+ Add recipe</Button>
                    </div>
                    <div className="card-body table-responsive">
                        <table className="table align-middle">
                            <thead><tr><th>Recipe</th><th>Finished product</th><th>Yield</th><th>Ingredients</th><th></th></tr></thead>
                            <tbody>
                                {recipes.length === 0 ? <tr><td colSpan={5} className="text-center text-muted py-4">No recipes.</td></tr>
                                    : recipes.map((r) => (<tr key={r.id}><td>{r.name}</td><td>{r.product?.name || r.product_id}</td><td>{r.yield_qty}</td>
                                        <td className="small text-muted">{(r.items || []).map((it) => `${it.product?.name || it.product_id}×${it.quantity}`).join(", ")}</td>
                                        <td className="text-end"><button className="btn btn-sm btn-link" onClick={() => openRecipe(r)}>Edit</button>
                                            <button className="btn btn-sm btn-link text-danger" onClick={() => delRecipe(r)}>Delete</button></td></tr>))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {tab === "production" && (
                <>
                    <div className="card mb-3"><div className="card-body">
                        <div className="d-flex flex-wrap gap-2 align-items-end">
                            <div><label className="form-label">Recipe</label>
                                <select className="form-control" value={run.recipe_id} onChange={(e) => setRun((f) => ({ ...f, recipe_id: e.target.value }))}>
                                    <option value="">—</option>{recipes.map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
                                </select></div>
                            <div><label className="form-label">Date</label>
                                <input type="date" className="form-control" value={run.date} onChange={(e) => setRun((f) => ({ ...f, date: e.target.value }))} /></div>
                            <div><label className="form-label">Batches</label>
                                <input type="number" className="form-control" style={{ width: 100 }} value={run.batches} onChange={(e) => setRun((f) => ({ ...f, batches: e.target.value }))} /></div>
                            <div><label className="form-label">Wastage</label>
                                <input type="number" className="form-control" style={{ width: 100 }} value={run.wastage_qty} onChange={(e) => setRun((f) => ({ ...f, wastage_qty: e.target.value }))} /></div>
                            <Button variant="primary" onClick={recordRun}>Record run</Button>
                        </div>
                    </div></div>
                    <div className="card"><div className="card-body table-responsive">
                        <table className="table align-middle">
                            <thead><tr><th>Date</th><th>Recipe</th><th>Batches</th><th>Produced</th><th>Wastage</th><th>Ingredients consumed</th></tr></thead>
                            <tbody>
                                {runs.length === 0 ? <tr><td colSpan={6} className="text-center text-muted py-4">No runs.</td></tr>
                                    : runs.map((r) => (<tr key={r.id}><td>{r.date ? new Date(r.date).toLocaleDateString() : "—"}</td><td>{r.recipe?.name || r.recipe_id}</td>
                                        <td>{r.batches}</td><td>{r.produced_qty}</td><td>{r.wastage_qty}</td>
                                        <td className="small text-muted">{(r.consumed || []).map((c) => `${prodName(c.product_id)}×${c.quantity}`).join(", ")}</td></tr>))}
                            </tbody>
                        </table>
                    </div></div>
                </>
            )}

            <Modal show={show} onHide={() => setShow(false)} size="lg">
                <Modal.Header closeButton><Modal.Title>{editing ? "Edit" : "Add"} recipe</Modal.Title></Modal.Header>
                <Modal.Body>
                    <div className="row">
                        <div className="col-md-6 mb-3"><label className="form-label">Recipe name *</label>
                            <input className="form-control" value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} /></div>
                        <div className="col-md-4 mb-3"><label className="form-label">Finished product *</label>
                            <select className="form-control" value={form.product_id} onChange={(e) => setForm((f) => ({ ...f, product_id: e.target.value }))}>
                                <option value="">—</option>{products.map((p) => <option key={p.id} value={p.id}>{pName(p)}</option>)}
                            </select></div>
                        <div className="col-md-2 mb-3"><label className="form-label">Yield</label>
                            <input type="number" className="form-control" value={form.yield_qty} onChange={(e) => setForm((f) => ({ ...f, yield_qty: e.target.value }))} /></div>
                    </div>
                    <div className="d-flex justify-content-between align-items-center mb-2">
                        <h6 className="mb-0">Ingredients (per yield unit)</h6>
                        <Button size="sm" variant="outline-primary" onClick={addItem}>+ Add</Button>
                    </div>
                    {form.items.map((it, i) => (
                        <div className="row g-2 mb-2 align-items-center" key={i}>
                            <div className="col-md-7"><select className="form-control" value={it.product_id} onChange={setItem(i, "product_id")}>
                                <option value="">— Ingredient —</option>{products.map((p) => <option key={p.id} value={p.id}>{pName(p)}</option>)}
                            </select></div>
                            <div className="col-md-3"><input type="number" className="form-control" placeholder="Qty" value={it.quantity} onChange={setItem(i, "quantity")} /></div>
                            <div className="col-md-2"><button className="btn btn-sm btn-link text-danger" onClick={() => removeItem(i)}>✕</button></div>
                        </div>
                    ))}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="primary" onClick={saveRecipe}>Save</Button>
                    <Button variant="secondary" onClick={() => setShow(false)}>Close</Button>
                </Modal.Footer>
            </Modal>
        </MasterLayout>
    );
};

export default BakeryProduction;
