import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const PlatformFeatures = () => {
    const dispatch = useDispatch();
    const [features, setFeatures] = useState([]);
    const [saving, setSaving] = useState(false);

    const load = () => {
        apiConfig
            .get(apiBaseURL.PLATFORM_FEATURES)
            .then((res) => setFeatures(res.data?.data || []))
            .catch(() => {});
    };

    useEffect(() => { load(); }, []);

    const toggle = (key) =>
        setFeatures((fs) =>
            fs.map((f) => (f.key === key ? { ...f, global_enabled: !f.global_enabled } : f))
        );

    const save = () => {
        setSaving(true);
        apiConfig
            .post(apiBaseURL.PLATFORM_FEATURES, {
                features: features.map((f) => ({ key: f.key, enabled: !!f.global_enabled })),
            })
            .then((res) => {
                setFeatures(res.data?.data || []);
                dispatch(addToast({ text: "Global features saved." }));
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
            <TabTitle title="Platform Features" />
            <div className="card">
                <div className="card-body">
                    <p className="text-muted">
                        Disabling a feature here hides it from every tenant, regardless of
                        their per-store setting.
                    </p>
                    {groups.map((g) => (
                        <div key={g} className="mb-4">
                            <h6 className="text-uppercase text-muted">{g}</h6>
                            <div className="row">
                                {features
                                    .filter((f) => f.group === g)
                                    .map((f) => (
                                        <div className="col-md-4 mb-2" key={f.key}>
                                            <label className="form-check form-switch">
                                                <input
                                                    type="checkbox"
                                                    className="form-check-input"
                                                    checked={!!f.global_enabled}
                                                    onChange={() => toggle(f.key)}
                                                />
                                                <span className="ms-2">{f.label}</span>
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

export default PlatformFeatures;
