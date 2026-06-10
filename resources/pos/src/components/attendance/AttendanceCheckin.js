import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faFingerprint, faFaceSmile, faBarcode, faQrcode, faHandPointer, faMugHot, faRightFromBracket, faClipboardList } from "@fortawesome/free-solid-svg-icons";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";
import ScanModal from "./ScanModal";
import FaceScanModal from "./FaceScanModal";
import { webauthnSupported, verifyDeviceBiometric } from "./webauthn";

const fmtHMS = (secs) => {
    const s = Math.max(0, Math.floor(secs || 0));
    const h = String(Math.floor(s / 3600)).padStart(2, "0");
    const m = String(Math.floor((s % 3600) / 60)).padStart(2, "0");
    const ss = String(s % 60).padStart(2, "0");
    return `${h}:${m}:${ss}`;
};

const MethodCard = ({ icon, label, onClick }) => (
    <button type="button" className="card h-100 border-0 shadow-sm attendance-method-card" onClick={onClick}
        style={{ cursor: "pointer", minWidth: 150 }}>
        <div className="card-body text-center py-4">
            <FontAwesomeIcon icon={icon} style={{ fontSize: 38 }} className="text-primary mb-2" />
            <div className="fw-semibold">{label}</div>
        </div>
    </button>
);

const AttendanceCheckin = () => {
    const dispatch = useDispatch();
    const [now, setNow] = useState(new Date());
    const [config, setConfig] = useState({});
    const [phase, setPhase] = useState("scan"); // scan | action
    const [identified, setIdentified] = useState(null);
    const [lastMethod, setLastMethod] = useState("manual");
    const [scan, setScan] = useState({ show: false, method: "barcode" });
    const [faceShow, setFaceShow] = useState(false);
    const [taskName, setTaskName] = useState("");
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        const t = setInterval(() => setNow(new Date()), 1000);
        return () => clearInterval(t);
    }, []);

    const loadConfig = () => {
        apiConfig.get(apiBaseURL.ATTENDANCE_STATUS)
            .then((res) => setConfig(res.data?.data?.settings || {}))
            .catch(() => {});
    };
    useEffect(() => { loadConfig(); }, []);

    const toast = (text, type) => dispatch(addToast({ text, type }));

    // ── identify, then branch on status ──────────────────────────────────────
    const identify = (method, extra = {}) => {
        setLastMethod(method);
        setBusy(true);
        return apiConfig.post(apiBaseURL.ATTENDANCE_IDENTIFY, { method, ...extra })
            .then((res) => {
                const status = res.data?.data;
                // First action of the day → immediate check-in (no task choice).
                if (status && !status.checked_in && !status.checked_out) {
                    return doCheckIn(status.employee.id, method);
                }
                if (status?.checked_out) {
                    toast(`${status.employee.name} already checked out today.`);
                    return null;
                }
                setIdentified(status);
                setPhase("action");
                return status;
            })
            .catch(({ response }) => toast(response?.data?.message || "Not recognised", toastType.ERROR))
            .finally(() => setBusy(false));
    };

    const refresh = (employeeId) =>
        apiConfig.post(apiBaseURL.ATTENDANCE_IDENTIFY, { method: "manual", employee_id: employeeId })
            .then((res) => { setIdentified(res.data?.data); return res.data?.data; })
            .catch(() => {});

    const doCheckIn = (employeeId, method) => {
        setBusy(true);
        return apiConfig.post(apiBaseURL.ATTENDANCE_CHECKIN, { method, employee_id: employeeId })
            .then((res) => {
                const st = res.data?.data?.status;
                toast(`Checked in — welcome ${st?.employee?.name || ""}!`);
                setIdentified(st);
                setPhase("action");
            })
            .catch(({ response }) => toast(response?.data?.message || "Check-in failed", toastType.ERROR))
            .finally(() => setBusy(false));
    };

    const doCheckOut = () => {
        const id = identified?.employee?.id;
        setBusy(true);
        apiConfig.post(apiBaseURL.ATTENDANCE_CHECKOUT, { method: lastMethod || "manual", employee_id: id })
            .then(() => { toast("Checked out. See you soon!"); reset(); })
            .catch(({ response }) => toast(response?.data?.message || "Check-out failed", toastType.ERROR))
            .finally(() => setBusy(false));
    };

    const startTask = (category) => {
        const id = identified?.employee?.id;
        if (!taskName.trim()) { toast("Enter a task name first", toastType.ERROR); return; }
        setBusy(true);
        apiConfig.post(apiBaseURL.ATTENDANCE_TASK_START, { name: taskName.trim(), category, employee_id: id })
            .then(() => { toast(`Started ${category} task`); setTaskName(""); return refresh(id); })
            .catch(({ response }) => toast(response?.data?.message || "Could not start task", toastType.ERROR))
            .finally(() => setBusy(false));
    };

    const breakAction = (start) => {
        const id = identified?.employee?.id;
        setBusy(true);
        apiConfig.post(start ? apiBaseURL.ATTENDANCE_BREAK_START : apiBaseURL.ATTENDANCE_BREAK_END, { employee_id: id })
            .then(() => { toast(start ? "Break started" : "Break ended"); return refresh(id); })
            .catch(({ response }) => toast(response?.data?.message || "Break action failed", toastType.ERROR))
            .finally(() => setBusy(false));
    };

    const reset = () => { setIdentified(null); setPhase("scan"); setTaskName(""); };

    const openScan = (method) => setScan({ show: true, method });
    const onScanned = (code) => { setScan((s) => ({ ...s, show: false })); identify(scan.method, { code }); };
    const onFace = (match) => { setFaceShow(false); identify("face", { employee_id: match.employee_id }); };

    const doWebauthn = async () => {
        setBusy(true);
        try {
            const result = await verifyDeviceBiometric(); // { employee_id, status }
            const status = result?.status;
            setLastMethod("webauthn");
            if (status && !status.checked_in && !status.checked_out) {
                await doCheckIn(result.employee_id, "webauthn");
            } else if (status?.checked_out) {
                toast(`${status.employee.name} already checked out today.`);
            } else if (status) {
                setIdentified(status);
                setPhase("action");
            }
        } catch (e) {
            toast("Device biometric failed or was cancelled.", toastType.ERROR);
        }
        setBusy(false);
    };

    const methods = [
        config.method_face && { key: "face", label: "Face Recognition", icon: faFaceSmile, onClick: () => setFaceShow(true) },
        config.method_fingerprint && config.fingerprint_mode === "webauthn" && webauthnSupported()
            ? { key: "webauthn", label: "Device Biometric", icon: faFingerprint, onClick: doWebauthn }
            : config.method_fingerprint && { key: "fingerprint", label: "Fingerprint", icon: faFingerprint, onClick: () => identify("fingerprint") },
        config.method_barcode && { key: "barcode", label: "Barcode Scan", icon: faBarcode, onClick: () => openScan("barcode") },
        config.method_qr && { key: "qr", label: "QR Code Scan", icon: faQrcode, onClick: () => openScan("qr") },
        config.method_manual && { key: "manual", label: "Manual (Me)", icon: faHandPointer, onClick: () => identify("manual") },
    ].filter(Boolean);

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Check In / Check Out" />

            {/* Clock header */}
            <div className="card mb-4">
                <div className="card-body text-center py-4">
                    <div style={{ fontSize: 56, fontWeight: 700, letterSpacing: 1 }}>
                        {now.toLocaleTimeString()}
                    </div>
                    <div className="text-muted">
                        {now.toLocaleDateString(undefined, { weekday: "long", year: "numeric", month: "long", day: "numeric" })}
                    </div>
                </div>
            </div>

            {config.enable_module === false ? (
                <div className="alert alert-light-warning">The attendance module is disabled in settings.</div>
            ) : phase === "scan" ? (
                <div className="card">
                    <div className="card-header"><h6 className="mb-0">Scan to mark attendance</h6></div>
                    <div className="card-body">
                        <div className="d-flex flex-wrap gap-3 justify-content-center">
                            {methods.length === 0
                                ? <div className="text-muted">No attendance methods are enabled.</div>
                                : methods.map((m) => <MethodCard key={m.key} icon={m.icon} label={m.label} onClick={m.onClick} />)}
                        </div>
                        {busy && <div className="text-center text-muted mt-3">Working…</div>}
                    </div>
                </div>
            ) : (
                /* Action panel — employee already checked in */
                <div className="card">
                    <div className="card-header d-flex justify-content-between align-items-center">
                        <h6 className="mb-0">Welcome, {identified?.employee?.name}</h6>
                        <button className="btn btn-sm btn-link" onClick={reset}>Switch employee</button>
                    </div>
                    <div className="card-body">
                        <div className="row mb-4">
                            <div className="col-md-4">
                                <div className="text-muted">Check-in time</div>
                                <div className="fw-bold">{identified?.check_in_at ? new Date(identified.check_in_at).toLocaleTimeString() : "—"}</div>
                            </div>
                            <div className="col-md-4">
                                <div className="text-muted">Working hours</div>
                                <div className="fw-bold">{fmtHMS(identified?.working_seconds)}</div>
                            </div>
                            <div className="col-md-4">
                                <div className="text-muted">Active task</div>
                                <div className="fw-bold">{identified?.active_task?.name || (identified?.on_break ? "On break" : "—")}</div>
                            </div>
                        </div>

                        {config.enable_task_tracking !== false && (
                            <div className="mb-4">
                                <label className="form-label">Task name</label>
                                <div className="d-flex flex-wrap gap-2">
                                    <input className="form-control" style={{ maxWidth: 320 }} value={taskName}
                                        onChange={(e) => setTaskName(e.target.value)} placeholder="e.g. Stock count" />
                                    <button className="btn btn-outline-primary" disabled={busy} onClick={() => startTask("office")}>
                                        <FontAwesomeIcon icon={faClipboardList} className="me-2" />Start Office Task
                                    </button>
                                    <button className="btn btn-outline-primary" disabled={busy} onClick={() => startTask("personal")}>
                                        <FontAwesomeIcon icon={faClipboardList} className="me-2" />Start Personal Task
                                    </button>
                                </div>
                            </div>
                        )}

                        <div className="d-flex flex-wrap gap-2">
                            {config.enable_break_tracking !== false && !identified?.on_break && (
                                <button className="btn btn-warning" disabled={busy} onClick={() => breakAction(true)}>
                                    <FontAwesomeIcon icon={faMugHot} className="me-2" />Start Break
                                </button>
                            )}
                            {config.enable_break_tracking !== false && identified?.on_break && (
                                <button className="btn btn-warning" disabled={busy} onClick={() => breakAction(false)}>
                                    <FontAwesomeIcon icon={faMugHot} className="me-2" />End Break
                                </button>
                            )}
                            <button className="btn btn-danger" disabled={busy} onClick={doCheckOut}>
                                <FontAwesomeIcon icon={faRightFromBracket} className="me-2" />Check Out
                            </button>
                        </div>
                    </div>
                </div>
            )}

            <ScanModal
                show={scan.show}
                title={scan.method === "qr" ? "Scan QR code" : "Scan barcode"}
                onDetected={onScanned}
                onClose={() => setScan((s) => ({ ...s, show: false }))}
            />
            <FaceScanModal
                show={faceShow}
                mode="identify"
                onIdentified={onFace}
                onClose={() => setFaceShow(false)}
            />
        </MasterLayout>
    );
};

export default AttendanceCheckin;
