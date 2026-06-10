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

const DisplayModal = ({ show, onHide, editData, stores, kitchens, onSaved }) => {
    const dispatch = useDispatch();
    const [form, setForm] = useState({ name: "", store_id: "", kitchen_id: "", status: true });

    useEffect(() => {
        setForm(editData
            ? { name: editData.name, store_id: editData.store_id, kitchen_id: editData.kitchen_id || "", status: !!editData.status }
            : { name: "", store_id: "", kitchen_id: "", status: true });
    }, [editData, show]);

    const submit = () => {
        if (!form.name || !form.store_id) {
            dispatch(addToast({ text: "Name and store are required", type: toastType.ERROR }));
            return;
        }
        const req = editData
            ? apiConfig.patch(apiBaseURL.CUSTOMER_DISPLAYS + "/" + editData.id, form)
            : apiConfig.post(apiBaseURL.CUSTOMER_DISPLAYS, form);
        req.then(() => { dispatch(addToast({ text: "Display saved." })); onSaved(); onHide(); })
            .catch(({ response }) => dispatch(addToast({ text: response?.data?.message || "Failed", type: toastType.ERROR })));
    };

    return (
        <Modal show={show} onHide={onHide}>
            <Modal.Header closeButton><Modal.Title>{editData ? "Edit Display" : "New Display"}</Modal.Title></Modal.Header>
            <Modal.Body>
                <div className="mb-3">
                    <label className="form-label">Name</label>
                    <input className="form-control" value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
                </div>
                <div className="mb-3">
                    <label className="form-label">Store</label>
                    <select className="form-control" value={form.store_id} onChange={(e) => setForm((f) => ({ ...f, store_id: e.target.value }))}>
                        <option value="">-- Select store --</option>
                        {(stores || []).map((s) => <option key={s.id} value={s.id}>{s?.attributes?.name}</option>)}
                    </select>
                </div>
                <div className="mb-3">
                    <label className="form-label">Kitchen (KOTs route here)</label>
                    <select className="form-control" value={form.kitchen_id || ""} onChange={(e) => setForm((f) => ({ ...f, kitchen_id: e.target.value }))}>
                        <option value="">-- None --</option>
                        {(kitchens || []).map((k) => <option key={k.id} value={k.id}>{k.name}</option>)}
                    </select>
                </div>
                <label className="form-check form-switch">
                    <input type="checkbox" className="form-check-input" checked={!!form.status} onChange={(e) => setForm((f) => ({ ...f, status: e.target.checked }))} />
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

const CustomerDisplays = () => {
    const dispatch = useDispatch();
    const stores = useSelector((s) => s.stores);
    const [items, setItems] = useState([]);
    const [kitchens, setKitchens] = useState([]);
    const [loading, setLoading] = useState(false);
    const [show, setShow] = useState(false);
    const [editData, setEditData] = useState(null);

    const load = () => {
        setLoading(true);
        apiConfig.get(apiBaseURL.CUSTOMER_DISPLAYS).then((res) => setItems(res.data?.data || [])).catch(() => {}).finally(() => setLoading(false));
    };
    useEffect(() => {
        if (!stores || stores.length === 0) dispatch(fetchStore());
        load();
        apiConfig.get(apiBaseURL.RESTAURANT_KITCHENS_LIST).then((res) => setKitchens(res.data?.data || [])).catch(() => {});
    }, []);

    const remove = (row) => {
        apiConfig.delete(apiBaseURL.CUSTOMER_DISPLAYS + "/" + row.id)
            .then(() => { dispatch(addToast({ text: "Deleted." })); load(); })
            .catch(({ response }) => dispatch(addToast({ text: response?.data?.message || "Failed", type: toastType.ERROR })));
    };

    const kioskUrl = (t) => `${window.location.origin}/kiosk/${t}`;

    const columns = [
        { name: "Name", selector: (r) => r.name },
        { name: "Store", selector: (r) => r?.store?.name || "—" },
        { name: "Kitchen", selector: (r) => r?.kitchen?.name || "—" },
        {
            name: "Kiosk link",
            cell: (r) => (
                <a href={kioskUrl(r.token)} target="_blank" rel="noopener noreferrer" className="btn btn-sm btn-outline-secondary">Open</a>
            ),
        },
        {
            name: "Action", right: true,
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
            <TabTitle title="Customer Displays" />
            <ReactDataTable
                columns={columns}
                items={items}
                isLoading={loading}
                onChange={load}
                pagination={false}
                isShowSearch
                AddButton={<div className="text-end"><Button onClick={() => { setEditData(null); setShow(true); }}>+ New Display</Button></div>}
            />
            <DisplayModal show={show} editData={editData} stores={stores} kitchens={kitchens} onHide={() => setShow(false)} onSaved={load} />
        </MasterLayout>
    );
};

export default CustomerDisplays;
