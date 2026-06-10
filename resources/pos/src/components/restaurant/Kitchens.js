import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Modal, Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";
import { fetchStore } from "../../store/action/storeAction";

const KitchenModal = ({ show, onHide, editData, stores, onSaved }) => {
    const dispatch = useDispatch();
    const [form, setForm] = useState({ name: "", store_id: "", status: true });

    useEffect(() => {
        setForm(
            editData
                ? { name: editData.name, store_id: editData.store_id, status: !!editData.status }
                : { name: "", store_id: "", status: true }
        );
    }, [editData, show]);

    const submit = () => {
        if (!form.name || !form.store_id) {
            dispatch(addToast({ text: "Name and store are required", type: toastType.ERROR }));
            return;
        }
        const req = editData
            ? apiConfig.patch(apiBaseURL.RESTAURANT_KITCHENS + "/" + editData.id, form)
            : apiConfig.post(apiBaseURL.RESTAURANT_KITCHENS, form);
        req
            .then(() => {
                dispatch(addToast({ text: "Kitchen saved." }));
                onSaved();
                onHide();
            })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to save", type: toastType.ERROR }))
            );
    };

    // Only restaurant-type stores can hold kitchens.
    const restaurantStores = (stores || []).filter(
        (s) => s?.attributes?.shop_type === "restaurant"
    );

    return (
        <Modal show={show} onHide={onHide}>
            <Modal.Header closeButton>
                <Modal.Title>{editData ? "Edit Kitchen" : "New Kitchen"}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div className="mb-3">
                    <label className="form-label">Store (restaurant)</label>
                    <select
                        className="form-control"
                        value={form.store_id}
                        onChange={(e) => setForm((f) => ({ ...f, store_id: e.target.value }))}
                    >
                        <option value="">-- Select store --</option>
                        {restaurantStores.map((s) => (
                            <option key={s.id} value={s.id}>{s?.attributes?.name}</option>
                        ))}
                    </select>
                    {restaurantStores.length === 0 && (
                        <span className="text-muted fs-small">
                            No restaurant-type stores. Set a store's type to "restaurant" first.
                        </span>
                    )}
                </div>
                <div className="mb-3">
                    <label className="form-label">Kitchen name</label>
                    <input
                        className="form-control"
                        value={form.name}
                        placeholder="e.g. Main Kitchen, Grill Station"
                        onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                    />
                </div>
                <label className="form-check form-switch">
                    <input
                        type="checkbox"
                        className="form-check-input"
                        checked={!!form.status}
                        onChange={(e) => setForm((f) => ({ ...f, status: e.target.checked }))}
                    />
                    <span className="ms-2">Active</span>
                </label>
            </Modal.Body>
            <Modal.Footer>
                <Button variant="primary" onClick={submit}>{editData ? "Update" : "Create"}</Button>
                <Button variant="secondary" onClick={onHide}>Cancel</Button>
            </Modal.Footer>
        </Modal>
    );
};

const Kitchens = () => {
    const dispatch = useDispatch();
    const stores = useSelector((s) => s.stores);
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(false);
    const [show, setShow] = useState(false);
    const [editData, setEditData] = useState(null);

    const [waiters, setWaiters] = useState([]);

    const load = () => {
        setLoading(true);
        apiConfig
            .get(apiBaseURL.RESTAURANT_KITCHENS)
            .then((res) => setItems(res.data?.data || []))
            .catch(() => {})
            .finally(() => setLoading(false));
    };

    const loadWaiters = () => {
        apiConfig
            .get("users?page[size]=0")
            .then((res) => {
                const rows = res.data?.data || [];
                // Keep waiter / kitchen users (they route or display KOTs).
                const filtered = rows.filter((u) => {
                    const roles = u?.attributes?.role || [];
                    return roles.some((r) => r?.name === "waiter" || r?.name === "kitchen");
                });
                setWaiters(filtered);
            })
            .catch(() => {});
    };

    useEffect(() => {
        if (!stores || stores.length === 0) dispatch(fetchStore());
        load();
        loadWaiters();
    }, []);

    const assignWaiter = (waiterId, kitchenId) => {
        apiConfig
            .post("restaurant/assign-waiter-kitchen", {
                waiter_id: waiterId,
                kitchen_id: kitchenId || null,
            })
            .then(() => {
                dispatch(addToast({ text: "Waiter kitchen updated." }));
                loadWaiters();
            })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to assign", type: toastType.ERROR }))
            );
    };

    const remove = (row) => {
        apiConfig
            .delete(apiBaseURL.RESTAURANT_KITCHENS + "/" + row.id)
            .then(() => {
                dispatch(addToast({ text: "Kitchen deleted." }));
                load();
            })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to delete", type: toastType.ERROR }))
            );
    };

    const columns = [
        { name: "Kitchen", selector: (r) => r.name },
        { name: "Store", selector: (r) => r?.store?.name || "—" },
        {
            name: "Status",
            cell: (r) =>
                r.status ? (
                    <span className="badge bg-light-success">Active</span>
                ) : (
                    <span className="badge bg-light-danger">Inactive</span>
                ),
        },
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
            <TabTitle title="Kitchens" />
            <ReactDataTable
                columns={columns}
                items={items}
                isLoading={loading}
                onChange={load}
                pagination={false}
                isShowSearch
                AddButton={
                    <div className="text-end">
                        <Button onClick={() => { setEditData(null); setShow(true); }}>+ New Kitchen</Button>
                    </div>
                }
            />
            <KitchenModal
                show={show}
                editData={editData}
                stores={stores}
                onHide={() => setShow(false)}
                onSaved={load}
            />

            <div className="card mt-4">
                <div className="card-header">
                    <h6 className="mb-0">Waiter → Kitchen routing</h6>
                </div>
                <div className="card-body">
                    {waiters.length === 0 ? (
                        <div className="text-muted">No waiter/kitchen users yet.</div>
                    ) : (
                        <table className="table table-sm align-middle mb-0">
                            <thead>
                                <tr><th>User</th><th>Role</th><th style={{ width: 260 }}>Kitchen</th></tr>
                            </thead>
                            <tbody>
                                {waiters.map((w) => (
                                    <tr key={w.id}>
                                        <td>
                                            {w?.attributes?.first_name} {w?.attributes?.last_name}
                                        </td>
                                        <td className="text-capitalize">
                                            {(w?.attributes?.role || []).map((r) => r?.name).join(", ")}
                                        </td>
                                        <td>
                                            <select
                                                className="form-control form-control-sm"
                                                value={w?.attributes?.kitchen_id || ""}
                                                onChange={(e) => assignWaiter(w.id, e.target.value)}
                                            >
                                                <option value="">— Unassigned —</option>
                                                {items.map((k) => (
                                                    <option key={k.id} value={k.id}>
                                                        {k.name} ({k?.store?.name})
                                                    </option>
                                                ))}
                                            </select>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                    <p className="text-muted mb-0 mt-2">
                        When a waiter sends an order to the kitchen, the KOT is routed to
                        their assigned kitchen's display.
                    </p>
                </div>
            </div>
        </MasterLayout>
    );
};

export default Kitchens;
