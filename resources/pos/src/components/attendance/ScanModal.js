import React, { useEffect, useRef, useState } from "react";
import { Button, Modal } from "react-bootstrap-v5";

/**
 * Camera barcode / QR scanner modal using html5-qrcode (loaded from CDN, so no
 * npm/build change). Calls onDetected(decodedText) once and closes.
 *
 * Also supports a physical USB scanner transparently: those type the code as
 * keystrokes ending in Enter, captured by the hidden input.
 */
// Self-hosted (see public/vendor/html5-qrcode).
const HTML5_QRCODE_SRC = window.HTML5_QRCODE_SRC || "/vendor/html5-qrcode/html5-qrcode.min.js";

let scriptPromise = null;
function loadLib() {
    if (window.Html5Qrcode) return Promise.resolve(window.Html5Qrcode);
    if (scriptPromise) return scriptPromise;
    scriptPromise = new Promise((resolve, reject) => {
        const s = document.createElement("script");
        s.src = HTML5_QRCODE_SRC;
        s.async = true;
        s.onload = () => resolve(window.Html5Qrcode);
        s.onerror = reject;
        document.body.appendChild(s);
    });
    return scriptPromise;
}

const ScanModal = ({ show, title = "Scan", onDetected, onClose }) => {
    const regionId = useRef(`scan-region-${Math.random().toString(36).slice(2)}`);
    const scannerRef = useRef(null);
    const [error, setError] = useState("");
    const [manual, setManual] = useState("");

    useEffect(() => {
        let active = true;
        if (!show) return undefined;
        setError("");

        loadLib()
            .then((Html5Qrcode) => {
                if (!active) return;
                const scanner = new Html5Qrcode(regionId.current, { verbose: false });
                scannerRef.current = scanner;
                return scanner.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (decodedText) => {
                        if (!active) return;
                        stop().then(() => onDetected && onDetected(decodedText));
                    },
                    () => {}
                );
            })
            .catch(() => {
                if (active) setError("Unable to start the camera. Use a USB scanner or type the code below.");
            });

        return () => {
            active = false;
            stop();
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show]);

    const stop = async () => {
        const scanner = scannerRef.current;
        scannerRef.current = null;
        if (scanner) {
            try {
                await scanner.stop();
                await scanner.clear();
            } catch (e) {
                /* already stopped */
            }
        }
    };

    const submitManual = (e) => {
        e.preventDefault();
        if (manual.trim()) {
            stop().then(() => onDetected && onDetected(manual.trim()));
            setManual("");
        }
    };

    return (
        <Modal show={show} onHide={() => { stop(); onClose && onClose(); }} centered>
            <Modal.Header closeButton>
                <Modal.Title>{title}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <div id={regionId.current} style={{ width: "100%", minHeight: 260 }} />
                {error && <div className="alert alert-light-warning mt-2">{error}</div>}
                <form onSubmit={submitManual} className="d-flex gap-2 mt-3">
                    <input
                        autoFocus
                        className="form-control"
                        placeholder="Or scan with USB / type code, then Enter"
                        value={manual}
                        onChange={(e) => setManual(e.target.value)}
                    />
                    <Button type="submit" variant="primary">Go</Button>
                </form>
            </Modal.Body>
            <Modal.Footer>
                <Button variant="secondary" onClick={() => { stop(); onClose && onClose(); }}>Cancel</Button>
            </Modal.Footer>
        </Modal>
    );
};

export default ScanModal;
