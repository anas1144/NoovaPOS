import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Modal, Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const emptyForm = { name: "", code: "", price: 0, status: true, items: [] };

const DealModal = ({ show, onHide, editData, products, onSaved }) => {
    const dispatch = useDispatch();
    const [form, setForm] = useState(emptyForm);

    useEffect(() => {
        setForm(
            editData
                ? {
                      name: editData.name,
                      code: editData.code || "",
                      price: editData.price,
                      status: !!editData.status,
                      items: (editData.items || []).map((i) => ({
                          product_id: i.product_id,
                          quantity: i.quantity,
                      })),
                  }
                : emptyForm
        );
    }, [editData, show]);

    const addItem = () =>
        setForm((f) => ({ ...f, items: [...f.items, { product_id: "", quantity: 1 }] }));
    const setItem = (i, k, v) =>
        setForm((f) => ({ ...f, items: f.items.map((it, idx) => (idx === i ? { ...it, [k]: v } : it)) }));
    const removeItem = (i) =>
        setForm((f) => ({ ...f, items: f.items.filter((_, idx) => idx !== i) }));

    const submit = () => {
        const items = form.items.filter((i) => i.product_id && Number(i.quantity) > 0);
        if (!form.name || items.length === 0) {
            dispatch(addToast({ text: "Name and at least one product are required", type: toastType.ERROR }));
            return;
        }
        const payload = { ...form, items };
        const req = editData
            ? apiConfig.post(apiBaseURL.DEALS + "/" + editData.id, payload)
            : apiConfig.post(apiBaseURL.DEALS, payload);
        req
            .then(() => { dispatch(addToast({ text: "Deal saved." })); onSaved(); onHide(); })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to save", type: toastType.ERROR }))
            );
    };

    return (
        <Modal show={show} onHide={onHide} size="lg">
            <Modal.Header closeButton>
                <Modal.Title>{editData ? "Edit Deal" : "New Deal"}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="row">
                    <div className="col-md-6 mb-3">
                        <label className="form-label">Deal name</label>
                        <input className="form-control" value={form.name}
                            onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
                    </div>
                    <div className="col-md-3 mb-3">
                        <label className="form-label">Code</label>
                        <input className="form-control" value={form.code}
                            onChange={(e) => setForm((f) => ({ ...f, code: e.target.value }))} />
                    </div>
                    <div className="col-md-3 mb-3">
                        <label className="form-label">Deal price</label>
                        <input type="number" min="0" className="form-control" value={form.price}
                            onChange={(e) => setForm((f) => ({ ...f, price: e.target.value }))} />
                    </div>
                </div>

                <div className="d-flex justify-content-between align-items-center">
                    <h6 className="mb-0">Products in this deal</h6>
                    <Button size="sm" variant="outline-primary" onClick={addItem}>+ Add product</Button>
                </div>
                {form.items.map((it, i) => (
                    <div className="row mt-2" key={i}>
                        <div className="col-md-7">
                            <select className="form-control" value={it.product_id}
                                onChange={(e) => setItem(i, "product_id", e.target.value)}>
                                <option value="">-- Select product --</option>
                                {products.map((p) => (
                                    <option key={p.id} value={p.id}>{p.name}</option>
                                ))}
                            </select>
                        </div>
                        <div className="col-md-3">
                            <input type="number" min="0.01" step="0.01" className="form-control"
                                value={it.quantity} onChange={(e) => setItem(i, "quantity", e.target.value)} />
                        </div>
                        <div className="col-md-2">
                            <button className="btn btn-outline-danger w-100" onClick={() => removeItem(i)}>✕</button>
                        </div>
                    </div>
                ))}
            </Modal.Body>
            <Modal.Footer>
                <Button variant="primary" onClick={submit}>{editData ? "Update" : "Create"}</Button>
                <Button variant="secondary" onClick={onHide}>Cancel</Button>
            </Modal.Footer>
        </Modal>
    );
};

const Deals = () => {
    const dispatch = useDispatch();
    const [deals, setDeals] = useState([]);
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(false);
    const [show, setShow] = useState(false);
    const [editData, setEditData] = useState(null);

    const load = () => {
        setLoading(true);
        apiConfig.get(apiBaseURL.DEALS)
            .then((res) => setDeals(res.data?.data || []))
            .catch(() => {})
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        load();
        apiConfig.get("get-all-products")
            .then((res) => setProducts(res.data?.data || []))
            .catch(() => {});
    }, []);

    const remove = (row) => {
        apiConfig.delete(apiBaseURL.DEALS + "/" + row.id)
            .then(() => { dispatch(addToast({ text: "Deal deleted." })); load(); })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to delete", type: toastType.ERROR }))
            );
    };

    const columns = [
        { name: "Deal", selector: (r) => r.name },
        { name: "Code", selector: (r) => r.code || "—" },
        { name: "Price", selector: (r) => r.price },
        { name: "Products", cell: (r) => (r.items || []).length },
        {
            name: "Action",
            right: true,
            cell: (r) => (
                <div className="d-flex gap-2">
                    <button className="btn btn-sm btn-outline-primary" onClick={() => { setEditData(r); setShow(true); }}>Edit</button>
                    <button className="btn btn-sm btn-outline-danger" onClick={() => remove(r)}>Delete</button>
                </div>
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Deals" />
            <ReactDataTable
                columns={columns}
                items={deals}
                isLoading={loading}
                onChange={load}
                pagination={false}
                isShowSearch
                AddButton={
                    <div className="text-end">
                        <Button onClick={() => { setEditData(null); setShow(true); }}>+ New Deal</Button>
                    </div>
                }
            />
            <DealModal
                show={show}
                editData={editData}
                products={products}
                onHide={() => setShow(false)}
                onSaved={load}
            />
        </MasterLayout>
    );
};

export default Deals;
