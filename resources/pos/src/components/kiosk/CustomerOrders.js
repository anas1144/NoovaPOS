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

const statusBadge = (s) => {
    const map = { new: "bg-light-warning", accepted: "bg-light-info", assigned: "bg-light-primary", served: "bg-light-success", cancelled: "bg-light-danger" };
    return <span className={`badge ${map[s] || "bg-light-secondary"} text-capitalize`}>{s}</span>;
};

const CustomerOrders = () => {
    const dispatch = useDispatch();
    const [rows, setRows] = useState([]);
    const [waiters, setWaiters] = useState([]);
    const [loading, setLoading] = useState(false);
    const [filter, setFilter] = useState("");

    const load = () => {
        setLoading(true);
        apiConfig.get(apiBaseURL.CUSTOMER_ORDERS, { params: filter ? { status: filter } : {} })
            .then((res) => setRows(res.data?.data || [])).catch(() => {}).finally(() => setLoading(false));
    };
    useEffect(() => { load(); }, [filter]);
    useEffect(() => {
        apiConfig.get("users?page[size]=0").then((res) => {
            const us = (res.data?.data || []).filter((u) => (u?.attributes?.role || []).some((r) => r?.name === "waiter"));
            setWaiters(us.map((u) => ({ id: u.id, name: `${u?.attributes?.first_name || ""} ${u?.attributes?.last_name || ""}`.trim() })));
        }).catch(() => {});
    }, []);

    const act = (id, action, body) => {
        apiConfig.post(`${apiBaseURL.CUSTOMER_ORDERS}/${id}/${action}`, body || {})
            .then(() => { dispatch(addToast({ text: "Updated." })); load(); })
            .catch(({ response }) => dispatch(addToast({ text: response?.data?.message || "Failed", type: toastType.ERROR })));
    };

    const columns = [
        { name: "Token", selector: (r) => r.token_no },
        { name: "Customer", selector: (r) => r.customer_name || "—" },
        { name: "Table", selector: (r) => r.table_id || "—" },
        { name: "Items", cell: (r) => (r.items || []).map((i) => `${i.name} x${i.quantity}`).join(", "), wrap: true },
        { name: "Total", selector: (r) => r.total },
        { name: "Status", cell: (r) => statusBadge(r.status) },
        {
            name: "Waiter",
            cell: (r) => (
                <select className="form-control form-control-sm" value={r.waiter_id || ""}
                    onChange={(e) => act(r.id, "assign", { waiter_id: e.target.value })}>
                    <option value="">— Assign —</option>
                    {waiters.map((w) => <option key={w.id} value={w.id}>{w.name}</option>)}
                </select>
            ),
        },
        {
            name: "Action", right: true,
            cell: (r) => (
                <div className="d-flex gap-2">
                    {r.status === "new" && <button className="btn btn-sm btn-outline-info" onClick={() => act(r.id, "accept")}>Accept</button>}
                    {r.status !== "served" && r.status !== "cancelled" && <button className="btn btn-sm btn-outline-success" onClick={() => act(r.id, "serve")}>Served</button>}
                </div>
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Customer Orders" />
            <ReactDataTable
                columns={columns}
                items={rows}
                isLoading={loading}
                onChange={load}
                pagination={false}
                isShowSearch
                AddButton={
                    <div className="d-flex gap-2 align-items-center">
                        <select className="form-control form-control-sm" style={{ width: 160 }} value={filter} onChange={(e) => setFilter(e.target.value)}>
                            <option value="">All</option>
                            {["new", "accepted", "assigned", "served", "cancelled"].map((s) => <option key={s} value={s}>{s}</option>)}
                        </select>
                        <Button variant="light-primary" onClick={load}>Refresh</Button>
                    </div>
                }
            />
        </MasterLayout>
    );
};

export default CustomerOrders;
