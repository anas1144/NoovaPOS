import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faPlay, faPause, faCheck, faClipboardList } from "@fortawesome/free-solid-svg-icons";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

const fmtHMS = (secs) => {
    const s = Math.max(0, Math.floor(secs || 0));
    const h = String(Math.floor(s / 3600)).padStart(2, "0");
    const m = String(Math.floor((s % 3600) / 60)).padStart(2, "0");
    const ss = String(s % 60).padStart(2, "0");
    return `${h}:${m}:${ss}`;
};

const statusBadge = (s) => ({
    running: "bg-light-success",
    paused: "bg-light-warning",
    completed: "bg-light-secondary",
}[s] || "bg-light-secondary");

const AttendanceTasks = () => {
    const dispatch = useDispatch();
    const [tasks, setTasks] = useState([]);
    const [name, setName] = useState("");
    const [busy, setBusy] = useState(false);

    const toast = (text, type) => dispatch(addToast({ text, type }));

    const load = () => {
        apiConfig.get(apiBaseURL.ATTENDANCE_TASKS)
            .then((res) => setTasks(res.data?.data || []))
            .catch(() => {});
    };
    useEffect(() => { load(); }, []);

    const start = (category) => {
        if (!name.trim()) { toast("Enter a task name first", toastType.ERROR); return; }
        setBusy(true);
        apiConfig.post(apiBaseURL.ATTENDANCE_TASK_START, { name: name.trim(), category })
            .then(() => { toast(`Started ${category} task`); setName(""); load(); })
            .catch(({ response }) => toast(response?.data?.message || "Could not start (are you checked in?)", toastType.ERROR))
            .finally(() => setBusy(false));
    };

    const act = (task, action) => {
        setBusy(true);
        apiConfig.post(`${apiBaseURL.ATTENDANCE_TASKS}/${task.id}/${action}`, {})
            .then(() => { toast(`Task ${action}d`); load(); })
            .catch(({ response }) => toast(response?.data?.message || "Action failed", toastType.ERROR))
            .finally(() => setBusy(false));
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Tasks" />

            <div className="card mb-4">
                <div className="card-header"><h6 className="mb-0">Start a task</h6></div>
                <div className="card-body">
                    <div className="d-flex flex-wrap gap-2 align-items-center">
                        <input className="form-control" style={{ maxWidth: 320 }} value={name}
                            onChange={(e) => setName(e.target.value)} placeholder="Task name e.g. Stock count" />
                        <button className="btn btn-outline-primary" disabled={busy} onClick={() => start("office")}>
                            <FontAwesomeIcon icon={faClipboardList} className="me-2" />Office Task
                        </button>
                        <button className="btn btn-outline-primary" disabled={busy} onClick={() => start("personal")}>
                            <FontAwesomeIcon icon={faClipboardList} className="me-2" />Personal Task
                        </button>
                    </div>
                    <div className="text-muted small mt-2">Only one task runs at a time — starting a new one pauses the current.</div>
                </div>
            </div>

            <div className="card">
                <div className="card-header"><h6 className="mb-0">Today's tasks</h6></div>
                <div className="card-body">
                    <div className="table-responsive">
                        <table className="table align-middle">
                            <thead>
                                <tr><th>Task</th><th>Category</th><th>Duration</th><th>Status</th><th className="text-end">Actions</th></tr>
                            </thead>
                            <tbody>
                                {tasks.length === 0 ? (
                                    <tr><td colSpan={5} className="text-center text-muted py-4">No tasks yet today.</td></tr>
                                ) : tasks.map((t) => (
                                    <tr key={t.id}>
                                        <td>{t.name}</td>
                                        <td className="text-capitalize">{t.category}</td>
                                        <td>{fmtHMS(t.duration_seconds)}</td>
                                        <td><span className={`badge ${statusBadge(t.status)} text-capitalize`}>{t.status}</span></td>
                                        <td className="text-end">
                                            {t.status === "running" && (
                                                <button className="btn btn-sm btn-outline-warning me-1" disabled={busy} onClick={() => act(t, "pause")}>
                                                    <FontAwesomeIcon icon={faPause} /> Pause
                                                </button>
                                            )}
                                            {t.status === "paused" && (
                                                <button className="btn btn-sm btn-outline-success me-1" disabled={busy} onClick={() => act(t, "resume")}>
                                                    <FontAwesomeIcon icon={faPlay} /> Resume
                                                </button>
                                            )}
                                            {t.status !== "completed" && (
                                                <button className="btn btn-sm btn-outline-primary" disabled={busy} onClick={() => act(t, "complete")}>
                                                    <FontAwesomeIcon icon={faCheck} /> Complete
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </MasterLayout>
    );
};

export default AttendanceTasks;
