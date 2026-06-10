import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { Button } from "react-bootstrap-v5";
import MasterLayout from "../MasterLayout";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import TabTitle from "../../shared/tab-title/TabTitle";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL, toastType } from "../../constants";
import { addToast } from "../../store/action/toastAction";
import FaceScanModal from "./FaceScanModal";

/**
 * Face enrolment: pick an employee, capture several samples from the camera
 * (front + slight left/right help accuracy), and save the descriptors.
 */
const FaceEnrollment = () => {
    const dispatch = useDispatch();
    const [employees, setEmployees] = useState([]);
    const [employeeId, setEmployeeId] = useState("");
    const [descriptors, setDescriptors] = useState([]);
    const [enrolled, setEnrolled] = useState([]);
    const [capture, setCapture] = useState(false);
    const [saving, setSaving] = useState(false);

    const toast = (text, type) => dispatch(addToast({ text, type }));

    const loadEmployees = () => {
        apiConfig.get(apiBaseURL.HR_EMPLOYEES, { params: { page_size: 0 } })
            .then((res) => setEmployees(res.data?.data?.data || res.data?.data || []))
            .catch(() => {});
    };
    const loadEnrolled = () => {
        apiConfig.get(apiBaseURL.ATTENDANCE_BIOMETRICS, { params: { type: "face" } })
            .then((res) => setEnrolled(res.data?.data || []))
            .catch(() => {});
    };
    useEffect(() => { loadEmployees(); loadEnrolled(); }, []);

    const onCaptured = (descriptor) => {
        setDescriptors((d) => [...d, descriptor]);
        toast(`Captured sample ${descriptors.length + 1}`);
    };

    const save = () => {
        if (!employeeId) { toast("Select an employee", toastType.ERROR); return; }
        if (descriptors.length === 0) { toast("Capture at least one face sample", toastType.ERROR); return; }
        setSaving(true);
        apiConfig.post(apiBaseURL.ATTENDANCE_FACE_ENROLL, { employee_id: Number(employeeId), descriptors })
            .then(() => { toast("Face enrolled."); setDescriptors([]); loadEnrolled(); })
            .catch(({ response }) => toast(response?.data?.message || "Enroll failed", toastType.ERROR))
            .finally(() => setSaving(false));
    };

    const empName = (e) => `${e.first_name || ""} ${e.last_name || ""}`.trim() + (e.employee_code ? ` (${e.employee_code})` : "");

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title="Face Enrollment" />

            <div className="row">
                <div className="col-lg-6 mb-4">
                    <div className="card h-100">
                        <div className="card-header"><h6 className="mb-0">Enroll a face</h6></div>
                        <div className="card-body">
                            <label className="form-label">Employee</label>
                            <select className="form-control mb-3" value={employeeId} onChange={(e) => { setEmployeeId(e.target.value); setDescriptors([]); }}>
                                <option value="">— Select employee —</option>
                                {employees.map((e) => <option key={e.id} value={e.id}>{empName(e)}</option>)}
                            </select>

                            <div className="d-flex align-items-center gap-2 mb-3">
                                <Button variant="outline-primary" disabled={!employeeId} onClick={() => setCapture(true)}>
                                    Open camera & capture
                                </Button>
                                <span className="badge bg-light-primary">{descriptors.length} sample(s)</span>
                                {descriptors.length > 0 && (
                                    <Button variant="link" className="text-danger" onClick={() => setDescriptors([])}>Clear</Button>
                                )}
                            </div>

                            <Button variant="primary" disabled={saving || descriptors.length === 0} onClick={save}>
                                {saving ? "Saving…" : "Save enrollment"}
                            </Button>
                            <p className="text-muted small mt-3 mb-0">
                                Capture 3–5 samples (front, slight left, slight right) for best accuracy.
                                Only the numeric face descriptors are stored — never raw images.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="col-lg-6 mb-4">
                    <div className="card h-100">
                        <div className="card-header"><h6 className="mb-0">Enrolled faces</h6></div>
                        <div className="card-body">
                            {enrolled.length === 0 ? (
                                <div className="text-muted text-center py-4">No faces enrolled yet.</div>
                            ) : (
                                <table className="table align-middle">
                                    <thead><tr><th>Employee</th><th>Samples</th><th>Last registered</th></tr></thead>
                                    <tbody>
                                        {enrolled.map((b) => (
                                            <tr key={b.id}>
                                                <td>{b.employee ? `${b.employee.first_name || ""} ${b.employee.last_name || ""}`.trim() : b.employee_id}</td>
                                                <td>{b.samples_count}</td>
                                                <td className="text-muted small">{b.last_registered_at ? new Date(b.last_registered_at).toLocaleString() : "—"}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <FaceScanModal
                show={capture}
                mode="capture"
                title="Capture face sample"
                onCaptured={onCaptured}
                onClose={() => setCapture(false)}
            />
        </MasterLayout>
    );
};

export default FaceEnrollment;
