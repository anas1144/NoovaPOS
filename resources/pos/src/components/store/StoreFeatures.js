import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";
import { fetchStore } from "../../store/action/storeAction";

const StoreFeatures = () => {
    const dispatch = useDispatch();
    const stores = useSelector((s) => s.stores);
    const [storeId, setStoreId] = useState("");
    const [features, setFeatures] = useState([]);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!stores || stores.length === 0) dispatch(fetchStore());
    }, []);

    useEffect(() => {
        if (stores && stores.length && !storeId) {
            setStoreId(stores[0].id);
        }
    }, [stores]);

    const load = (id) => {
        if (!id) return;
        apiConfig
            .get(apiBaseURL.STORE_FEATURES, { params: { store_id: id } })
            .then((res) => setFeatures(res.data?.data || []))
            .catch(() => {});
    };

    useEffect(() => { load(storeId); }, [storeId]);

    const toggle = (key) =>
        setFeatures((fs) =>
            fs.map((f) => (f.key === key ? { ...f, store_enabled: !f.store_enabled } : f))
        );

    const save = () => {
        setSaving(true);
        apiConfig
            .post(apiBaseURL.STORE_FEATURES, {
                store_id: storeId,
                features: features.map((f) => ({ key: f.key, enabled: !!f.store_enabled })),
            })
            .then((res) => {
                setFeatures(res.data?.data || []);
                dispatch(addToast({ text: "Store features saved." }));
            })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to save", type: toastType.ERROR }))
            )
            .finally(() => setSaving(false));
    };

    const groups = [...new Set(features.map((f) => f.group))];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Store Features" />
            <div className="card">
                <div className="card-body">
                    <div className="mb-3" style={{ maxWidth: 320 }}>
                        <label className="form-label">Store / Branch</label>
                        <select
                            className="form-control"
                            value={storeId}
                            onChange={(e) => setStoreId(e.target.value)}
                        >
                            {(stores || []).map((s) => (
                                <option key={s.id} value={s.id}>{s?.attributes?.name}</option>
                            ))}
                        </select>
                    </div>

                    {groups.map((g) => (
                        <div key={g} className="mb-4">
                            <h6 className="text-uppercase text-muted">{g}</h6>
                            <div className="row">
                                {features
                                    .filter((f) => f.group === g)
                                    .map((f) => (
                                        <div className="col-md-4 mb-2" key={f.key}>
                                            <label
                                                className="form-check form-switch"
                                                title={!f.global_enabled ? "Disabled by the platform" : ""}
                                            >
                                                <input
                                                    type="checkbox"
                                                    className="form-check-input"
                                                    checked={!!f.store_enabled && !!f.global_enabled}
                                                    disabled={!f.global_enabled}
                                                    onChange={() => toggle(f.key)}
                                                />
                                                <span className="ms-2">
                                                    {f.label}
                                                    {!f.global_enabled && (
                                                        <span className="badge bg-light-secondary ms-2">Platform-off</span>
                                                    )}
                                                </span>
                                            </label>
                                        </div>
                                    ))}
                            </div>
                        </div>
                    ))}
                    <Button variant="primary" disabled={saving} onClick={save}>
                        {saving ? "Saving…" : "Save"}
                    </Button>
                </div>
            </div>
        </MasterLayout>
    );
};

export default StoreFeatures;
