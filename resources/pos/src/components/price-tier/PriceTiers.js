import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const PriceTiers = () => {
    const dispatch = useDispatch();
    const [tiers, setTiers] = useState([]);
    const [loading, setLoading] = useState(false);
    const [newLabel, setNewLabel] = useState("");

    const load = () => {
        setLoading(true);
        apiConfig
            .get(apiBaseURL.PRICE_TIERS)
            .then((res) => setTiers(res.data?.data || []))
            .catch(() => {})
            .finally(() => setLoading(false));
    };

    useEffect(() => { load(); }, []);

    const add = () => {
        if (!newLabel.trim()) return;
        apiConfig
            .post(apiBaseURL.PRICE_TIERS, { label: newLabel })
            .then(() => { setNewLabel(""); dispatch(addToast({ text: "Tier added." })); load(); })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to add", type: toastType.ERROR }))
            );
    };

    const remove = (row) => {
        apiConfig
            .delete(apiBaseURL.PRICE_TIERS + "/" + row.id)
            .then(() => { dispatch(addToast({ text: "Tier deleted." })); load(); })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to delete", type: toastType.ERROR }))
            );
    };

    const columns = [
        { name: "Key", selector: (r) => r.key },
        { name: "Label", selector: (r) => r.label },
        {
            name: "Default",
            cell: (r) => (r.is_default ? <span className="badge bg-light-primary">Default (Retail)</span> : "—"),
        },
        {
            name: "Action",
            right: true,
            cell: (r) =>
                r.is_default ? (
                    <span className="text-muted small">—</span>
                ) : (
                    <button className="btn btn-sm btn-outline-danger" onClick={() => remove(r)}>Delete</button>
                ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Price Tiers" />
            <ReactDataTable
                columns={columns}
                items={tiers}
                isLoading={loading}
                onChange={load}
                pagination={false}
                isShowSearch
                AddButton={
                    <div className="d-flex gap-2 align-items-center">
                        <input
                            className="form-control form-control-sm"
                            style={{ width: 200 }}
                            placeholder="New tier (e.g. Distributor)"
                            value={newLabel}
                            onChange={(e) => setNewLabel(e.target.value)}
                        />
                        <Button onClick={add}>+ Add Tier</Button>
                    </div>
                }
            />
            <p className="text-muted mt-2">
                "Retail" is the default tier and uses each product's base price. Other
                tiers store per-product overrides set on the product form.
            </p>
        </MasterLayout>
    );
};

export default PriceTiers;
