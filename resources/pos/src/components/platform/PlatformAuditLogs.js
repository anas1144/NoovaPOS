import React, { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import moment from "moment";
import { fetchPlatformAuditLogs } from "../../store/action/platformAction";

const PlatformAuditLogs = () => {
    const dispatch = useDispatch();
    const logs = useSelector((s) => s.platform?.auditLogs || []);
    const isLoading = useSelector((s) => s.isLoading);

    useEffect(() => {
        dispatch(fetchPlatformAuditLogs());
    }, []);

    const columns = [
        {
            name: "When",
            selector: (r) => r.created_at,
            cell: (r) =>
                r.created_at ? moment(r.created_at).format("YYYY-MM-DD HH:mm") : "-",
        },
        {
            name: "Event",
            selector: (r) => r.event,
            cell: (r) => (
                <span className="badge bg-light-primary text-capitalize">
                    {(r.event || "").replace(/_/g, " ")}
                </span>
            ),
        },
        {
            name: "Tenant",
            selector: (r) => r.tenant_name || r.tenant_id,
        },
        {
            name: "Actor",
            cell: (r) =>
                r.actor
                    ? `${r.actor.first_name || ""} ${r.actor.last_name || ""} (${r.actor.email || ""})`
                    : "System",
        },
        { name: "IP", selector: (r) => r.ip_address || "-" },
        {
            name: "Auditable",
            cell: (r) => `${r.auditable_type || "-"} #${r.auditable_id || "-"}`,
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Audit Logs" />
            <ReactDataTable
                columns={columns}
                items={logs}
                isLoading={isLoading}
                pagination={false}
                isShowSearch
                onChange={() => dispatch(fetchPlatformAuditLogs())}
            />
        </MasterLayout>
    );
};

export default PlatformAuditLogs;
