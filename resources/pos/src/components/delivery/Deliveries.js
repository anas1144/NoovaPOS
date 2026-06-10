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

const STATUSES = ["pending", "assigned", "out_for_delivery", "delivered", "cancelled"];

const statusBadge = (s) => {
    const map = {
        delivered: "bg-light-success",
        out_for_delivery: "bg-light-primary",
        assigned: "bg-light-info",
        pending: "bg-light-warning",
        cancelled: "bg-light-danger",
    };
    return <span className={`badge ${map[s] || "bg-light-secondary"} text-capitalize`}>{(s || "pending").replace(/_/g, " ")}</span>;
};

const Deliveries = () => {
    const dispatch = useDispatch();
    const [rows, setRows] = useState([]);
    const [boys, setBoys] = useState([]);
    const [loading, setLoading] = useState(false);
    const [filter, setFilter] = useState("");

    const load = () => {
        setLoading(true);
        apiConfig
            .get(apiBaseURL.DELIVERIES, { params: filter ? { delivery_status: filter } : {} })
            .then((res) => setRows(res.data?.data || []))
            .catch(() => {})
            .finally(() => setLoading(false));
    };

    useEffect(() => { load(); }, [filter]);
    useEffect(() => {
        apiConfig.get(apiBaseURL.DELIVERY_BOYS).then((res) => setBoys(res.data?.data || [])).catch(() => {});
    }, []);

    const assign = (id, boyId) => {
        if (!boyId) return;
        apiConfig.post(apiBaseURL.DELIVERIES + "/" + id + "/assign", { delivery_boy_id: boyId })
            .then(() => { dispatch(addToast({ text: "Assigned." })); load(); })
            .catch(({ response }) => dispatch(addToast({ text: response?.data?.message || "Failed", type: toastType.ERROR })));
    };

    const setStatus = (id, status) => {
        apiConfig.post(apiBaseURL.DELIVERIES + "/" + id + "/status", { delivery_status: status })
            .then(() => { dispatch(addToast({ text: "Status updated." })); load(); })
            .catch(({ response }) => dispatch(addToast({ text: response?.data?.message || "Failed", type: toastType.ERROR })));
    };

    const columns = [
        { name: "Order", selector: (r) => r.reference_code || r.id },
        { name: "Customer", selector: (r) => r.customer || "—" },
        { name: "Phone", selector: (r) => r.phone || "—" },
        { name: "Address", selector: (r) => r.delivery_address || "—", wrap: true },
        { name: "Total", selector: (r) => r.grand_total },
        { name: "Status", cell: (r) => statusBadge(r.delivery_status) },
        {
            name: "Delivery Boy",
            cell: (r) => (
                <select
                    className="form-control form-control-sm"
                    value={r.delivery_boy_id || ""}
                    onChange={(e) => assign(r.id, e.target.value)}
                >
                    <option value="">— Assign —</option>
                    {boys.map((b) => (
                        <option key={b.id} value={b.id}>{b.name}</option>
                    ))}
                </select>
            ),
        },
        {
            name: "Update",
            right: true,
            cell: (r) => (
                <select
                    className="form-control form-control-sm"
                    value={r.delivery_status || "pending"}
                    onChange={(e) => setStatus(r.id, e.target.value)}
                >
                    {STATUSES.map((s) => (
                        <option key={s} value={s}>{s.replace(/_/g, " ")}</option>
                    ))}
                </select>
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Deliveries" />
            <ReactDataTable
                columns={columns}
                items={rows}
                isLoading={loading}
                onChange={load}
                pagination={false}
                isShowSearch
                AddButton={
                    <div className="d-flex gap-2 align-items-center">
                        <select className="form-control form-control-sm" style={{ width: 180 }}
                            value={filter} onChange={(e) => setFilter(e.target.value)}>
                            <option value="">All statuses</option>
                            {STATUSES.map((s) => (
                                <option key={s} value={s}>{s.replace(/_/g, " ")}</option>
                            ))}
                        </select>
                        <Button variant="light-primary" onClick={load}>Refresh</Button>
                    </div>
                }
            />
        </MasterLayout>
    );
};

export default Deliveries;
