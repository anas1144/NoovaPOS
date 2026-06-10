/**
 * face-api.js helper — loaded from CDN at runtime so no npm dependency / build
 * change is required. Provides: load models, compute a face descriptor from a
 * <video>, and match a descriptor against enrolled employees.
 *
 * Matching is pure client-side: we only ever send/receive the 128-float
 * descriptors (non-reversible), never raw face images.
 */

// Self-hosted (see public/vendor/face-api). Override via window globals if you
// ever need to point elsewhere.
const FACEAPI_SRC = window.FACEAPI_SRC || "/vendor/face-api/face-api.min.js";
// Model weights (tiny detector + landmarks + recognition) — run
// `npm run fetch:face-models` once to populate public/vendor/face-api/models.
const MODEL_URL = window.FACEAPI_MODEL_URL || "/vendor/face-api/models";

let loadPromise = null;

function injectScript(src) {
    return new Promise((resolve, reject) => {
        if (window.faceapi) return resolve(window.faceapi);
        const existing = document.querySelector(`script[src="${src}"]`);
        if (existing) {
            existing.addEventListener("load", () => resolve(window.faceapi));
            existing.addEventListener("error", reject);
            return;
        }
        const s = document.createElement("script");
        s.src = src;
        s.async = true;
        s.onload = () => resolve(window.faceapi);
        s.onerror = reject;
        document.body.appendChild(s);
    });
}

/** Load face-api.js + its models once. Returns the faceapi global. */
export async function loadFaceApi() {
    if (loadPromise) return loadPromise;
    loadPromise = (async () => {
        const faceapi = await injectScript(FACEAPI_SRC);
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        ]);
        return faceapi;
    })().catch((e) => {
        loadPromise = null; // allow retry
        throw e;
    });
    return loadPromise;
}

/** Compute a single face descriptor (plain number[]) from a video element. */
export async function descriptorFromVideo(video) {
    const faceapi = await loadFaceApi();
    const det = await faceapi
        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.4 }))
        .withFaceLandmarks()
        .withFaceDescriptor();
    if (!det) return null;
    return Array.from(det.descriptor);
}

/** Euclidean distance between two equal-length numeric vectors. */
function distance(a, b) {
    let sum = 0;
    for (let i = 0; i < a.length; i += 1) {
        const d = a[i] - b[i];
        sum += d * d;
    }
    return Math.sqrt(sum);
}

/**
 * Match a live descriptor against enrolled employees.
 * @param {number[]} descriptor       live face descriptor
 * @param {Array}    enrolled         [{ employee_id, name, descriptors: number[][] }]
 * @param {number}   threshold        max distance to accept (lower = stricter)
 * @returns {object|null}             best match { employee_id, name, distance } or null
 */
export function matchDescriptor(descriptor, enrolled, threshold = 0.55) {
    let best = null;
    (enrolled || []).forEach((emp) => {
        (emp.descriptors || []).forEach((d) => {
            if (!Array.isArray(d) || d.length !== descriptor.length) return;
            const dist = distance(descriptor, d);
            if (best === null || dist < best.distance) {
                best = { employee_id: emp.employee_id, name: emp.name, distance: dist };
            }
        });
    });
    return best && best.distance <= threshold ? best : null;
}
