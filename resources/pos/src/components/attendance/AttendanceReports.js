import React, { useEffect, useState } from "react";
import {
    Chart as ChartJS, CategoryScale, LinearScale, BarElement, LineElement, PointElement, Tooltip, Legend,
} from "chart.js";
import { Bar } from "react-chartjs-2";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL } from "../../constants";

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Tooltip, Legend);

const today = () => new Date().toISOString().slice(0, 10);
const daysAgo = (n) => new Date(Date.now() - n * 864e5).toISOString().slice(0, 10);

const Stat = ({ label, value, tone = "primary" }) => (
    <div className="col-md-3 col-6 mb-3">
        <div className="card h-100"><div className="card-body py-3">
            <div className="text-muted small text-capitalize">{label}</div>
            <div className={`fw-bold fs-4 text-${tone}`}>{value}</div>
        </div></div>
    </div>
);

const AttendanceReports = () => {
    const [from, setFrom] = useState(daysAgo(29));
    const [to, setTo] = useState(today());
    const [employees, setEmployees] = useState([]);
    const [employeeId, setEmployeeId] = useState("");
    const [summary, setSummary] = useState(null);
    const [productivity, setProductivity] = useState(null);
    const [performance, setPerformance] = useState([]);

    useEffect(() => {
        apiConfig.get(apiBaseURL.HR_EMPLOYEES, { params: { page_size: 0 } })
            .then((res) => setEmployees(res.data?.data?.data || res.data?.data || []))
            .catch(() => {});
    }, []);

    const run = () => {
        const params = { from, to, ...(employeeId ? { employee_id: employeeId } : {}) };
        apiConfig.get(apiBaseURL.ATTENDANCE_REPORT_SUMMARY, { params }).then((r) => setSummary(r.data?.data)).catch(() => {});
        apiConfig.get(apiBaseURL.ATTENDANCE_REPORT_PRODUCTIVITY, { params }).then((r) => setProductivity(r.data?.data)).catch(() => {});
        apiConfig.get(apiBaseURL.ATTENDANCE_REPORT_PERFORMANCE, { params }).then((r) => setPerformance(r.data?.data?.rows || [])).catch(() => {});
    };
    useEffect(() => { run(); /* initial */ }, []); // eslint-disable-line

    const daily = summary?.daily || [];
    const chart = {
        labels: daily.map((d) => d.date.slice(5)),
        datasets: [
            { label: "Present", data: daily.map((d) => d.present), backgroundColor: "#22c55e", borderRadius: 4 },
            { label: "Hours", data: daily.map((d) => Math.round(d.hours)), backgroundColor: "#6366f1", borderRadius: 4 },
        ],
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Attendance Reports" />

            <div className="card mb-4">
                <div className="card-body">
                    <div className="row align-items-end g-2">
                        <div className="col-md-3">
                            <label className="form-label">From</label>
                            <input type="date" className="form-control" value={from} onChange={(e) => setFrom(e.target.value)} />
                        </div>
                        <div className="col-md-3">
                            <label className="form-label">To</label>
                            <input type="date" className="form-control" value={to} onChange={(e) => setTo(e.target.value)} />
                        </div>
                        <div className="col-md-4">
                            <label className="form-label">Employee (optional)</label>
                            <select className="form-control" value={employeeId} onChange={(e) => setEmployeeId(e.target.value)}>
                                <option value="">All employees</option>
                                {employees.map((e) => <option key={e.id} value={e.id}>{`${e.first_name || ""} ${e.last_name || ""}`.trim()}</option>)}
                            </select>
                        </div>
                        <div className="col-md-2">
                            <button className="btn btn-primary w-100" onClick={run}>Run</button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="row">
                <Stat label="Records" value={summary?.totals?.records ?? 0} />
                <Stat label="Present" value={summary?.totals?.present ?? 0} tone="success" />
                <Stat label="Late" value={summary?.totals?.late ?? 0} tone="warning" />
                <Stat label="Work hours" value={summary?.totals?.work_hours ?? 0} />
            </div>

            <div className="row">
                <Stat label="Work hours" value={productivity?.work_hours ?? 0} />
                <Stat label="Break hours" value={productivity?.break_hours ?? 0} tone="warning" />
                <Stat label="Task hours" value={productivity?.task_hours ?? 0} tone="info" />
                <Stat label="Overtime" value={productivity?.overtime ?? 0} tone="danger" />
            </div>

            <div className="card mb-4">
                <div className="card-header"><h6 className="mb-0">Daily attendance & hours</h6></div>
                <div className="card-body" style={{ minHeight: 280 }}>
                    {daily.length === 0 ? <div className="text-muted text-center py-5">No data for this range.</div>
                        : <Bar data={chart} options={{ responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }} />}
                </div>
            </div>

            <div className="card">
                <div className="card-header"><h6 className="mb-0">Employee performance</h6></div>
                <div className="card-body table-responsive">
                    <table className="table align-middle">
                        <thead><tr>
                            <th>Employee</th><th>Present days</th><th>Attendance rate</th><th>Avg hours</th><th>Tasks</th><th>Task completion</th>
                        </tr></thead>
                        <tbody>
                            {performance.length === 0 ? (
                                <tr><td colSpan={6} className="text-center text-muted py-4">No data.</td></tr>
                            ) : performance.map((r) => (
                                <tr key={r.employee_id}>
                                    <td>{r.name} {r.employee_code ? <span className="text-muted small">({r.employee_code})</span> : null}</td>
                                    <td>{r.present_days}</td>
                                    <td>{r.attendance_rate}%</td>
                                    <td>{r.avg_hours}</td>
                                    <td>{r.tasks}</td>
                                    <td>{r.task_completion}%</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </MasterLayout>
    );
};

export default AttendanceReports;
