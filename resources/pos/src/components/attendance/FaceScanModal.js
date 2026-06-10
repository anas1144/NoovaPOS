import React, { useEffect, useRef, useState } from "react";
import { Button, Modal } from "react-bootstrap-v5";
import apiConfig from "../../config/apiConfig";
import { apiBaseURL } from "../../constants";
import { loadFaceApi, descriptorFromVideo, matchDescriptor } from "./faceApi";

/**
 * Camera face modal.
 *  - mode "identify": match the live face against enrolled employees and call
 *    onIdentified({ employee_id, name, distance }).
 *  - mode "capture":  return the captured descriptor via onCaptured(descriptor)
 *    (used by enrollment to gather several samples).
 */
const FaceScanModal = ({ show, mode = "identify", title, onClose, onIdentified, onCaptured }) => {
    const videoRef = useRef(null);
    const streamRef = useRef(null);
    const [status, setStatus] = useState("Loading camera…");
    const [busy, setBusy] = useState(false);
    const enrolledRef = useRef([]);

    useEffect(() => {
        let active = true;
        if (!show) return undefined;
        setStatus("Loading camera…");

        (async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } });
                if (!active) { stream.getTracks().forEach((t) => t.stop()); return; }
                streamRef.current = stream;
                if (videoRef.current) {
                    videoRef.current.srcObject = stream;
                    await videoRef.current.play().catch(() => {});
                }
                setStatus("Loading face models…");
                await loadFaceApi();
                if (mode === "identify") {
                    const res = await apiConfig.get(apiBaseURL.ATTENDANCE_FACE_DATA);
                    enrolledRef.current = res.data?.data || [];
                }
                if (active) setStatus("Look at the camera and capture.");
            } catch (e) {
                if (active) setStatus("Camera/model load failed. Check permissions or use another method.");
            }
        })();

        return () => {
            active = false;
            stopCamera();
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show]);

    const stopCamera = () => {
        const stream = streamRef.current;
        streamRef.current = null;
        if (stream) stream.getTracks().forEach((t) => t.stop());
    };

    const capture = async () => {
        if (!videoRef.current) return;
        setBusy(true);
        setStatus("Reading face…");
        try {
            const descriptor = await descriptorFromVideo(videoRef.current);
            if (!descriptor) {
                setStatus("No face detected — try again, with your face centred.");
                setBusy(false);
                return;
            }
            if (mode === "capture") {
                onCaptured && onCaptured(descriptor);
                setStatus("Captured. You can capture another sample or close.");
                setBusy(false);
                return;
            }
            const match = matchDescriptor(descriptor, enrolledRef.current);
            if (match) {
                stopCamera();
                onIdentified && onIdentified(match);
            } else {
                setStatus("Face not recognised. Try again or use another method.");
            }
        } catch (e) {
            setStatus("Could not read the face. Try again.");
        }
        setBusy(false);
    };

    return (
        <Modal show={show} onHide={() => { stopCamera(); onClose && onClose(); }} centered>
            <Modal.Header closeButton>
                <Modal.Title>{title || (mode === "capture" ? "Capture face" : "Face check-in")}</Modal.Title>
            </Modal.Header>
            <Modal.Body className="text-center">
                <div className="position-relative d-inline-block">
                    <video
                        ref={videoRef}
                        muted
                        playsInline
                        style={{ width: "100%", maxWidth: 360, borderRadius: 12, background: "#000" }}
                    />
                </div>
                <div className="text-muted mt-2">{status}</div>
            </Modal.Body>
            <Modal.Footer>
                <Button variant="primary" disabled={busy} onClick={capture}>
                    {busy ? "Reading…" : (mode === "capture" ? "Capture sample" : "Capture & match")}
                </Button>
                <Button variant="secondary" onClick={() => { stopCamera(); onClose && onClose(); }}>Close</Button>
            </Modal.Footer>
        </Modal>
    );
};

export default FaceScanModal;
