import React, { useEffect, useState } from "react";
import { Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import ReactDataTable from "../../shared/table/ReactDataTable";
import apiConfig from "../../config/apiConfig";
import moment from "moment";

const STATUS_OPTIONS = [
    { value: "", label: "All" },
    { value: "present", label: "Present" },
    { value: "late", label: "Late" },
    { value: "half_day", label: "Half day" },
    { value: "leave", label: "Leave" },
    { value: "absent", label: "Absent" },
];

const statusBadge = (s) => {
    const m = {
        present: "bg-light-success",
        late: "bg-light-warning",
        half_day: "bg-light-info",
        leave: "bg-light-secondary",
        absent: "bg-light-danger",
    };
    return `badge text-capitalize ${m[s] || "bg-light-primary"}`;
};

const Attendance = () => {
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(false);
    const [status, setStatus] = useState("");
    const [date, setDate] = useState(moment().format("YYYY-MM-DD"));

    const load = async () => {
        setLoading(true);
        try {
            const res = await apiConfig.get("hr/attendance", {
                params: { date, status: status || undefined },
            });
            setItems(res.data?.data?.data || res.data?.data || []);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, [date, status]);

    const updateStatus = async (id, newStatus) => {
        await apiConfig.patch("hr/attendance/" + id, { status: newStatus });
        load();
    };

    const columns = [
        {
            name: "Date",
            cell: (r) => moment(r.date).format("ddd, DD MMM YYYY"),
        },
        {
            name: "Employee",
            cell: (r) =>
                r.employee
                    ? `${r.employee.first_name} ${r.employee.last_name || ""}`
                    : "—",
        },
        {
            name: "Code",
            selector: (r) => r.employee?.employee_code || "—",
        },
        {
            name: "Check-in",
            cell: (r) =>
                r.check_in_at ? moment(r.check_in_at).format("HH:mm") : "—",
        },
        {
            name: "Check-out",
            cell: (r) =>
                r.check_out_at ? moment(r.check_out_at).format("HH:mm") : "—",
        },
        {
            name: "Hours",
            cell: (r) =>
                r.hours_worked ? Number(r.hours_worked).toFixed(2) : "—",
        },
        {
            name: "Status",
            cell: (r) => (
                <span className={statusBadge(r.status)}>{r.status}</span>
            ),
        },
        {
            name: "Action",
            right: true,
            cell: (r) => (
                <select
                    className="form-control form-control-sm"
                    style={{ width: 130 }}
                    value={r.status || ""}
                    onChange={(e) => updateStatus(r.id, e.target.value)}
                >
                    {STATUS_OPTIONS.filter((o) => o.value).map((o) => (
                        <option key={o.value} value={o.value}>
                            {o.label}
                        </option>
                    ))}
                </select>
            ),
        },
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Attendance" />
            <div className="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h4 className="mb-0">Daily Attendance</h4>
                <div className="d-flex gap-2 align-items-center">
                    <label className="form-label mb-0 small">Date</label>
                    <input
                        type="date"
                        className="form-control"
                        style={{ width: 160 }}
                        value={date}
                        onChange={(e) => setDate(e.target.value)}
                    />
                    <select
                        className="form-control"
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                    >
                        {STATUS_OPTIONS.map((o) => (
                            <option key={o.value} value={o.value}>
                                {o.label}
                            </option>
                        ))}
                    </select>
                    <Button variant="outline-primary" onClick={load}>
                        Refresh
                    </Button>
                </div>
            </div>
            <ReactDataTable
                columns={columns}
                items={items}
                isLoading={loading}
                pagination={false}
                isShowSearch
                onChange={load}
            />
        </MasterLayout>
    );
};

export default Attendance;
