import React, { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import { fetchOfflineDevices } from "../../store/action/offlineDeviceAction";
import moment from "moment";

const OfflineDevices = () => {
    const dispatch = useDispatch();
    const devices = useSelector((state) => state.offlineSync?.devices || []);
    const isLoading = useSelector((state) => state.isLoading);

    useEffect(() => {
        dispatch(fetchOfflineDevices());
    }, []);

    const items = (devices || []).map((d) => ({
        id: d.id,
        name: d.name,
        device_uuid: d.device_uuid,
        platform: d.platform,
        app_version: d.app_version,
        status: d.status,
        store_name: d.store?.name || "-",
        shop_name: d.shop?.name || "-",
        last_seen_at: d.last_seen_at,
    }));

    const columns = [
        { name: "Device Name", selector: (r) => r.name },
        {
            name: "UUID",
            selector: (r) => r.device_uuid,
            cell: (r) => (
                <code className="small">{(r.device_uuid || "").slice(0, 18)}</code>
            ),
        },
        {
            name: "Platform",
            selector: (r) => r.platform,
            cell: (r) => (
                <span className="badge bg-light-primary text-capitalize">
                    {r.platform || "-"}
                </span>
            ),
        },
        { name: "App Version", selector: (r) => r.app_version || "-" },
        { name: "Store", selector: (r) => r.store_name },
        { name: "Shop", selector: (r) => r.shop_name },
        {
            name: "Status",
            cell: (r) => (
                <span
                    className={`badge ${
                        r.status ? "bg-light-success" : "bg-light-secondary"
                    }`}
                >
                    {r.status ? "Active" : "Inactive"}
                </span>
            ),
        },
        {
            name: "Last Seen",
            selector: (r) => r.last_seen_at,
            cell: (r) =>
                r.last_seen_at
                    ? moment(r.last_seen_at).fromNow()
                    : "Never",
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Offline Devices" />
            <ReactDataTable
                columns={columns}
                items={items}
                isLoading={isLoading}
                onChange={() => dispatch(fetchOfflineDevices())}
                pagination={false}
                isShowSearch
            />
        </MasterLayout>
    );
};

export default OfflineDevices;
