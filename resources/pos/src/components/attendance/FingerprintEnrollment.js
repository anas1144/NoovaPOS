import React, { useEffect, useRef, useState } from "react";
import { useDispatch } from "react-redux";
import { Button, Modal } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";
import { webauthnSupported, registerDeviceBiometric } from "./webauthn";

/**
 * Fingerprint enrolment (future-ready). The webcam capture is a VISUAL record,
 * not biometric matching — real 1:N matching needs an external fingerprint
 * scanner, configured under Attendance → Devices (no-code connector), or
 * WebAuthn. This screen records that a profile exists and how many samples were
 * captured.
 */
const FingerprintEnrollment = () => {
    const dispatch = useDispatch();
    const [employees, setEmployees] = useState([]);
    const [employeeId, setEmployeeId] = useState("");
    const [enrolled, setEnrolled] = useState([]);
    const [samples, setSamples] = useState(0);
    const [show, setShow] = useState(false);
    const [saving, setSaving] = useState(false);
    const videoRef = useRef(null);
    const streamRef = useRef(null);

    const toast = (text, type) => dispatch(addToast({ text, type }));

    const loadEmployees = () => {
        apiConfig.get(apiBaseURL.HR_EMPLOYEES, { params: { page_size: 0 } })
            .then((res) => setEmployees(res.data?.data?.data || res.data?.data || []))
            .catch(() => {});
    };
    const loadEnrolled = () => {
        apiConfig.get(apiBaseURL.ATTENDANCE_BIOMETRICS, { params: { type: "fingerprint" } })
            .then((res) => setEnrolled(res.data?.data || []))
            .catch(() => {});
    };
    useEffect(() => { loadEmployees(); loadEnrolled(); }, []);

    const openCamera = async () => {
        setSamples(0);
        setShow(true);
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
            streamRef.current = stream;
            if (videoRef.current) { videoRef.current.srcObject = stream; await videoRef.current.play().catch(() => {}); }
        } catch (e) {
            toast("Could not open the camera.", toastType.ERROR);
        }
    };

    const stopCamera = () => {
        const s = streamRef.current; streamRef.current = null;
        if (s) s.getTracks().forEach((t) => t.stop());
    };

    const close = () => { stopCamera(); setShow(false); };

    const capture = () => setSamples((n) => n + 1); // visual sample counter

    const save = () => {
        if (!employeeId) { toast("Select an employee", toastType.ERROR); return; }
        if (samples === 0) { toast("Capture at least one sample", toastType.ERROR); return; }
        setSaving(true);
        apiConfig.post(apiBaseURL.ATTENDANCE_FINGERPRINT_ENROLL, { employee_id: Number(employeeId), samples })
            .then(() => { toast("Fingerprint profile saved (future-ready)."); close(); loadEnrolled(); })
            .catch(({ response }) => toast(response?.data?.message || "Save failed", toastType.ERROR))
            .finally(() => setSaving(false));
    };

    const enrollWebauthn = async () => {
        if (!employeeId) { toast("Select an employee", toastType.ERROR); return; }
        try {
            await registerDeviceBiometric(Number(employeeId), navigator.platform || "device");
            toast("Device biometric registered for real matching.");
        } catch (e) {
            toast(e?.message || "Device biometric registration was cancelled.", toastType.ERROR);
        }
    };

    const empName = (e) => `${e.first_name || ""} ${e.last_name || ""}`.trim() + (e.employee_code ? ` (${e.employee_code})` : "");

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Fingerprint Enrollment" />

            <div className="alert alert-light-primary">
                <strong>Note:</strong> camera capture stores a visual finger record (future-ready), not a
                biometric match. For real fingerprint matching connect a scanner under
                <strong> Attendance → Devices</strong>.
            </div>

            <div className="row">
                <div className="col-lg-6 mb-4">
                    <div className="card h-100">
                        <div className="card-header"><h6 className="mb-0">Enroll fingerprint</h6></div>
                        <div className="card-body">
                            <label className="form-label">Employee</label>
                            <select className="form-control mb-3" value={employeeId} onChange={(e) => setEmployeeId(e.target.value)}>
                                <option value="">— Select employee —</option>
                                {employees.map((e) => <option key={e.id} value={e.id}>{empName(e)}</option>)}
                            </select>
                            <Button variant="outline-primary" disabled={!employeeId} onClick={openCamera}>Open camera</Button>
                            {webauthnSupported() && (
                                <Button variant="primary" className="ms-2" disabled={!employeeId} onClick={enrollWebauthn}>
                                    Register device biometric (real match)
                                </Button>
                            )}
                            <p className="text-muted small mt-3 mb-0">
                                <strong>Device biometric (WebAuthn)</strong> uses this device's own
                                fingerprint/face sensor for real matching — no external hardware. The
                                employee must enroll on the device they'll check in from.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="col-lg-6 mb-4">
                    <div className="card h-100">
                        <div className="card-header"><h6 className="mb-0">Enrolled fingerprints</h6></div>
                        <div className="card-body">
                            {enrolled.length === 0 ? (
                                <div className="text-muted text-center py-4">None enrolled yet.</div>
                            ) : (
                                <table className="table align-middle">
                                    <thead><tr><th>Employee</th><th>Samples</th><th>Status</th></tr></thead>
                                    <tbody>
                                        {enrolled.map((b) => (
                                            <tr key={b.id}>
                                                <td>{b.employee ? `${b.employee.first_name || ""} ${b.employee.last_name || ""}`.trim() : b.employee_id}</td>
                                                <td>{b.samples_count}</td>
                                                <td><span className="badge bg-light-secondary text-capitalize">{b.status}</span></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <Modal show={show} onHide={close} centered>
                <Modal.Header closeButton><Modal.Title>Capture finger samples</Modal.Title></Modal.Header>
                <Modal.Body className="text-center">
                    <video ref={videoRef} muted playsInline style={{ width: "100%", maxWidth: 360, borderRadius: 12, background: "#000" }} />
                    <div className="mt-2">Samples captured: <strong>{samples}</strong></div>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="outline-primary" onClick={capture}>Capture sample</Button>
                    <Button variant="primary" disabled={saving || samples === 0} onClick={save}>{saving ? "Saving…" : "Save profile"}</Button>
                    <Button variant="secondary" onClick={close}>Close</Button>
                </Modal.Footer>
            </Modal>
        </MasterLayout>
    );
};

export default FingerprintEnrollment;
