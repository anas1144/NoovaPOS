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

const PlatformShopTypes = () => {
    const dispatch = useDispatch();
    const [types, setTypes] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [newLabel, setNewLabel] = useState("");

    const load = () => {
        setIsLoading(true);
        apiConfig
            .get(apiBaseURL.PLATFORM_SHOP_TYPES)
            .then((res) => setTypes(res.data?.data || []))
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to load shop types", type: toastType.ERROR }))
            )
            .finally(() => setIsLoading(false));
    };

    useEffect(() => { load(); }, []);

    const toggle = (row) => {
        apiConfig
            .patch(apiBaseURL.PLATFORM_SHOP_TYPES + "/" + row.id, { enabled: !row.enabled })
            .then(() => {
                dispatch(addToast({ text: `${row.label} ${!row.enabled ? "enabled" : "disabled"}.` }));
                load();
            })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to update", type: toastType.ERROR }))
            );
    };

    const addType = () => {
        if (!newLabel.trim()) return;
        apiConfig
            .post(apiBaseURL.PLATFORM_SHOP_TYPES, { label: newLabel, enabled: true })
            .then(() => {
                setNewLabel("");
                dispatch(addToast({ text: "Shop type added." }));
                load();
            })
            .catch(({ response }) =>
                dispatch(addToast({ text: response?.data?.message || "Failed to add", type: toastType.ERROR }))
            );
    };

    const columns = [
        { name: "Key", selector: (r) => r.key },
        { name: "Label", selector: (r) => r.label },
        {
            name: "Enabled",
            cell: (r) => (
                <label className="form-check form-switch form-switch-sm">
                    <input
                        type="checkbox"
                        className="form-check-input cursor-pointer"
                        checked={!!r.enabled}
                        onChange={() => toggle(r)}
                    />
                </label>
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Shop Types" />
            <ReactDataTable
                columns={columns}
                items={types}
                isLoading={isLoading}
                onChange={load}
                pagination={false}
                isShowSearch
                AddButton={
                    <div className="d-flex gap-2 align-items-center">
                        <input
                            className="form-control form-control-sm"
                            style={{ width: 200 }}
                            placeholder="New type label"
                            value={newLabel}
                            onChange={(e) => setNewLabel(e.target.value)}
                        />
                        <Button onClick={addType}>+ Add</Button>
                    </div>
                }
            />
        </MasterLayout>
    );
};

export default PlatformShopTypes;
