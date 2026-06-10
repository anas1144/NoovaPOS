import React, { useEffect, useRef, useState } from "react";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
    faUserCheck, faUserXmark, faClock, faMugHot, faPersonRunning,
    faClipboardList, faCircleCheck, faBusinessTime, faPercent,
} from "@fortawesome/free-solid-svg-icons";
import {
    Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement,
} from "chart.js";
import { Doughnut, Bar } from "react-chartjs-2";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL } from "../../constants";

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement);

const Card = ({ icon, label, value, tone = "primary" }) => (
    <div className="col-xl-3 col-md-4 col-sm-6 mb-3">
        <div className="card h-100">
            <div className="card-body d-flex align-items-center">
                <div className={`rounded-circle d-flex align-items-center justify-content-center me-3 bg-light-${tone}`}
                    style={{ width: 48, height: 48 }}>
                    <FontAwesomeIcon icon={icon} className={`text-${tone}`} />
                </div>
                <div>
                    <div className="text-muted small text-capitalize">{label}</div>
                    <div className="fw-bold fs-4">{value}</div>
                </div>
            </div>
        </div>
    </div>
);

const fmtTime = (d) => (d ? new Date(d).toLocaleTimeString() : "");

const AttendanceDashboard = () => {
    const [data, setData] = useState(null);
    const timer = useRef(null);

    const load = () => {
        apiConfig.get(apiBaseURL.ATTENDANCE_DASHBOARD)
            .then((res) => setData(res.data?.data || null))
            .catch(() => {});
    };

    useEffect(() => {
        load();
        timer.current = setInterval(load, 15000); // AJAX polling
        return () => clearInterval(timer.current);
    }, []);

    const c = data?.cards || {};
    const timeline = data?.timeline || [];

    const donut = {
        labels: ["Present", "Absent", "Late"],
        datasets: [{
            data: [c.present || 0, c.absent || 0, c.late || 0],
            backgroundColor: ["#22c55e", "#ef4444", "#f59e0b"],
            borderWidth: 0,
        }],
    };

    const bar = {
        labels: ["Working", "On Break", "Active Tasks", "Completed"],
        datasets: [{
            label: "Now",
            data: [c.working || 0, c.on_break || 0, c.active_tasks || 0, c.completed_tasks || 0],
            backgroundColor: ["#6366f1", "#f59e0b", "#0ea5e9", "#22c55e"],
            borderRadius: 6,
        }],
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Attendance Dashboard" />

            <div className="row">
                <Card icon={faUserCheck}      label="Present"          value={c.present ?? 0}            tone="success" />
                <Card icon={faUserXmark}      label="Absent"           value={c.absent ?? 0}             tone="danger" />
                <Card icon={faClock}          label="Late"             value={c.late ?? 0}               tone="warning" />
                <Card icon={faMugHot}         label="On Break"         value={c.on_break ?? 0}           tone="warning" />
                <Card icon={faPersonRunning}  label="Working"          value={c.working ?? 0}            tone="primary" />
                <Card icon={faClipboardList}  label="Active Tasks"     value={c.active_tasks ?? 0}       tone="info" />
                <Card icon={faCircleCheck}    label="Completed Tasks"  value={c.completed_tasks ?? 0}    tone="success" />
                <Card icon={faBusinessTime}   label="Total Work Hours" value={c.total_work_hours ?? 0}   tone="primary" />
                <Card icon={faPercent}        label="Attendance %"     value={`${c.attendance_percent ?? 0}%`} tone="info" />
            </div>

            <div className="row">
                <div className="col-lg-4 mb-4">
                    <div className="card h-100">
                        <div className="card-header"><h6 className="mb-0">Today's Attendance</h6></div>
                        <div className="card-body d-flex align-items-center justify-content-center" style={{ minHeight: 260 }}>
                            <div style={{ maxWidth: 240 }}><Doughnut data={donut} /></div>
                        </div>
                    </div>
                </div>
                <div className="col-lg-4 mb-4">
                    <div className="card h-100">
                        <div className="card-header"><h6 className="mb-0">Status</h6></div>
                        <div className="card-body" style={{ minHeight: 260 }}>
                            <Bar data={bar} options={{ plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }} />
                        </div>
                    </div>
                </div>
                <div className="col-lg-4 mb-4">
                    <div className="card h-100">
                        <div className="card-header"><h6 className="mb-0">Live Activity</h6></div>
                        <div className="card-body" style={{ minHeight: 260, maxHeight: 320, overflowY: "auto" }}>
                            {timeline.length === 0 ? (
                                <div className="text-muted text-center py-4">No activity yet today.</div>
                            ) : (
                                <ul className="list-unstyled mb-0">
                                    {timeline.map((e, i) => (
                                        <li key={i} className="d-flex justify-content-between border-bottom py-2">
                                            <span>{e.text}</span>
                                            <span className="text-muted small">{fmtTime(e.at)}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </MasterLayout>
    );
};

export default AttendanceDashboard;
