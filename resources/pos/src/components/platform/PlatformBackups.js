import React, { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import moment from "moment";
import { fetchPlatformBackups } from "../../store/action/platformAction";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL } from "../../constants";

const statusBadge = (s) => {
    const map = {
        queued: "bg-light-warning",
        running: "bg-light-info",
        completed: "bg-light-success",
        failed: "bg-light-danger",
    };
    return `badge text-capitalize ${map[s] || "bg-light-secondary"}`;
};

const PlatformBackups = () => {
    const dispatch = useDispatch();
    const backups = useSelector((s) => s.platform?.backups || []);
    const isLoading = useSelector((s) => s.isLoading);

    useEffect(() => {
        dispatch(fetchPlatformBackups());
    }, []);

    const download = async (id) => {
        try {
            const res = await apiConfig.get(
                apiBaseURL.PLATFORM_BACKUPS + "/" + id + "/download",
                { responseType: "blob" }
            );
            const url = window.URL.createObjectURL(new Blob([res.data]));
            const link = document.createElement("a");
            link.href = url;
            link.setAttribute("download", `tenant-backup-${id}.zip`);
            document.body.appendChild(link);
            link.click();
            link.remove();
        } catch (e) {
            console.error(e);
        }
    };

    const columns = [
        { name: "Tenant", selector: (r) => r.tenant_name || r.tenant_id },
        {
            name: "Type",
            cell: (r) => (
                <span className="badge bg-light-primary text-capitalize">
                    {r.backup_type}
                </span>
            ),
        },
        {
            name: "Status",
            cell: (r) => <span className={statusBadge(r.status)}>{r.status}</span>,
        },
        {
            name: "Size",
            cell: (r) =>
                r.size_bytes
                    ? `${(r.size_bytes / 1024 / 1024).toFixed(1)} MB`
                    : "-",
        },
        {
            name: "Started",
            cell: (r) => (r.started_at ? moment(r.started_at).fromNow() : "-"),
        },
        {
            name: "Completed",
            cell: (r) =>
                r.completed_at ? moment(r.completed_at).fromNow() : "-",
        },
        {
            name: "Action",
            right: true,
            cell: (r) =>
                r.status === "completed" ? (
                    <button
                        className="btn btn-sm btn-outline-primary"
                        onClick={() => download(r.id)}
                    >
                        Download
                    </button>
                ) : (
                    <span className="text-muted">-</span>
                ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Tenant Backups" />
            <ReactDataTable
                columns={columns}
                items={backups}
                isLoading={isLoading}
                pagination={false}
                isShowSearch
                onChange={() => dispatch(fetchPlatformBackups())}
            />
        </MasterLayout>
    );
};

export default PlatformBackups;
