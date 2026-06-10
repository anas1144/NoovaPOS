import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";

// Boolean toggles grouped for the UI.
const GROUPS = [
    {
        title: "General", keys: [
            ["enable_module", "Enable attendance module"],
            ["enable_self_checkin", "Allow employee self check-in"],
            ["enable_self_checkout", "Allow employee self check-out"],
            ["enable_task_tracking", "Enable task tracking"],
            ["enable_break_tracking", "Enable break tracking"],
            ["enable_dashboard", "Enable attendance dashboard"],
        ],
    },
    {
        title: "Attendance methods", keys: [
            ["method_face", "Face recognition"],
            ["method_fingerprint", "Fingerprint"],
            ["method_barcode", "Barcode scanner"],
            ["method_qr", "QR scanner"],
            ["method_manual", "Manual attendance"],
        ],
    },
    {
        title: "Scanner", keys: [
            ["scanner_pos_camera", "Use camera scanner"],
            ["auto_submit_on_scan", "Auto-submit on scan"],
            ["sound_success", "Play success sound"],
            ["sound_error", "Play error sound"],
            ["camera_mobile", "Enable mobile camera"],
            ["camera_desktop", "Enable desktop camera"],
        ],
    },
    {
        title: "Security", keys: [
            ["prevent_multiple_checkins", "Prevent multiple check-ins"],
            ["require_active_shift", "Require active shift"],
            ["require_location", "Require location validation"],
            ["self_only", "Require self attendance only"],
            ["allow_admin_manual", "Allow admin manual attendance"],
        ],
    },
];

const AttendanceSettings = () => {
    const dispatch = useDispatch();
    const [s, setS] = useState(null);
    const [saving, setSaving] = useState(false);

    const toast = (text, type) => dispatch(addToast({ text, type }));

    useEffect(() => {
        apiConfig.get(apiBaseURL.ATTENDANCE_SETTINGS)
            .then((res) => setS(res.data?.data?.settings || {}))
            .catch(() => {});
    }, []);

    const toggle = (k) => setS((p) => ({ ...p, [k]: !p[k] }));
    const setVal = (k, v) => setS((p) => ({ ...p, [k]: v }));

    const save = () => {
        setSaving(true);
        apiConfig.post(apiBaseURL.ATTENDANCE_SETTINGS, { settings: s })
            .then((res) => { setS(res.data?.data?.settings || s); toast("Attendance settings saved."); })
            .catch(({ response }) => toast(response?.data?.message || "Save failed", toastType.ERROR))
            .finally(() => setSaving(false));
    };

    if (!s) {
        return <MasterLayout><TopProgressBar /><TabTitle title="Attendance Configuration" /><div className="text-muted">Loading…</div></MasterLayout>;
    }

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Attendance Configuration" />

            <div className="row">
                {GROUPS.map((g) => (
                    <div className="col-lg-6 mb-4" key={g.title}>
                        <div className="card h-100">
                            <div className="card-header"><h6 className="mb-0">{g.title}</h6></div>
                            <div className="card-body">
                                {g.keys.map(([key, label]) => (
                                    <div className="form-check form-switch mb-2" key={key}>
                                        <input className="form-check-input" type="checkbox" id={`set_${key}`}
                                            checked={!!s[key]} onChange={() => toggle(key)} />
                                        <label className="form-check-label" htmlFor={`set_${key}`}>{label}</label>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                ))}

                <div className="col-lg-6 mb-4">
                    <div className="card h-100">
                        <div className="card-header"><h6 className="mb-0">Biometric matching mode</h6></div>
                        <div className="card-body">
                            <label className="form-label">Face matching</label>
                            <select className="form-control mb-3" value={s.face_mode || "camera"} onChange={(e) => setVal("face_mode", e.target.value)}>
                                <option value="camera">Camera (built-in face-api.js)</option>
                                <option value="device">External device / cloud API</option>
                            </select>

                            <label className="form-label">Fingerprint matching</label>
                            <select className="form-control mb-3" value={s.fingerprint_mode || "camera_visual"} onChange={(e) => setVal("fingerprint_mode", e.target.value)}>
                                <option value="camera_visual">Camera visual record only (no match)</option>
                                <option value="device">External fingerprint scanner</option>
                                <option value="webauthn">WebAuthn (device sensor)</option>
                            </select>

                            <div className="form-check form-switch">
                                <input className="form-check-input" type="checkbox" id="set_enable_device_connectors"
                                    checked={!!s.enable_device_connectors} onChange={() => toggle("enable_device_connectors")} />
                                <label className="form-check-label" htmlFor="set_enable_device_connectors">Enable device connectors</label>
                            </div>
                            <p className="text-muted small mt-2 mb-0">Configure devices under Attendance → Devices.</p>
                        </div>
                    </div>
                </div>
            </div>

            <button className="btn btn-primary" type="button" disabled={saving} onClick={save}>
                {saving ? "Saving…" : "Save settings"}
            </button>
        </MasterLayout>
    );
};

export default AttendanceSettings;
