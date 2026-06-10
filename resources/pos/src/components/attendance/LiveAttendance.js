import React, { useEffect, useRef, useState } from "react";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faPersonRunning, faMugHot, faClipboardList } from "@fortawesome/free-solid-svg-icons";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL } from "../../constants";

const fmtHMS = (secs) => {
    const s = Math.max(0, Math.floor(secs || 0));
    const h = String(Math.floor(s / 3600)).padStart(2, "0");
    const m = String(Math.floor((s % 3600) / 60)).padStart(2, "0");
    return `${h}h ${m}m`;
};

const stateBadge = (state) => {
    if (state === "on_break") return { cls: "bg-light-warning", icon: faMugHot, label: "On break" };
    if (state === "on_task") return { cls: "bg-light-info", icon: faClipboardList, label: "On task" };
    return { cls: "bg-light-success", icon: faPersonRunning, label: "Working" };
};

const LiveAttendance = () => {
    const [rows, setRows] = useState([]);
    const [updatedAt, setUpdatedAt] = useState(null);
    const timer = useRef(null);

    const load = () => {
        apiConfig.get(apiBaseURL.ATTENDANCE_LIVE)
            .then((res) => { setRows(res.data?.data || []); setUpdatedAt(new Date()); })
            .catch(() => {});
    };

    useEffect(() => {
        load();
        timer.current = setInterval(load, 7000); // AJAX polling
        return () => clearInterval(timer.current);
    }, []);

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Live Attendance" />

            <div className="card">
                <div className="card-header d-flex justify-content-between align-items-center">
                    <h6 className="mb-0">Currently active employees ({rows.length})</h6>
                    {updatedAt && <span className="text-muted small">Updated {updatedAt.toLocaleTimeString()}</span>}
                </div>
                <div className="card-body">
                    {rows.length === 0 ? (
                        <div className="text-muted text-center py-5">No one is currently checked in.</div>
                    ) : (
                        <div className="row">
                            {rows.map((r) => {
                                const b = stateBadge(r.state);
                                return (
                                    <div className="col-xl-3 col-md-4 col-sm-6 mb-3" key={r.attendance_id}>
                                        <div className="card h-100 border">
                                            <div className="card-body">
                                                <div className="d-flex justify-content-between align-items-start">
                                                    <div className="fw-bold">{r.name}</div>
                                                    <span className={`badge ${b.cls}`}>
                                                        <FontAwesomeIcon icon={b.icon} className="me-1" />{b.label}
                                                    </span>
                                                </div>
                                                <div className="text-muted small">{r.employee_code || ""}</div>
                                                <hr className="my-2" />
                                                <div className="d-flex justify-content-between">
                                                    <span className="text-muted small">In</span>
                                                    <span className="small">{r.check_in_at ? new Date(r.check_in_at).toLocaleTimeString() : "—"}</span>
                                                </div>
                                                <div className="d-flex justify-content-between">
                                                    <span className="text-muted small">Worked</span>
                                                    <span className="small fw-semibold">{fmtHMS(r.working_seconds)}</span>
                                                </div>
                                                {r.active_task && (
                                                    <div className="mt-1 small"><FontAwesomeIcon icon={faClipboardList} className="me-1 text-info" />{r.active_task}</div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </MasterLayout>
    );
};

export default LiveAttendance;
