/**
 * Downloads the face-api.js model weights into public/vendor/face-api/models so
 * face recognition runs fully self-hosted (no CDN at runtime).
 *
 * Run once on a machine with internet:
 *     npm run fetch:face-models
 *
 * After that, attendance face recognition works offline / behind a firewall.
 */
const fs = require("fs");
const path = require("path");
const https = require("https");

const BASE = process.env.FACE_MODELS_URL
    || "https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights";

const FILES = [
    "tiny_face_detector_model-weights_manifest.json",
    "tiny_face_detector_model-shard1",
    "face_landmark_68_model-weights_manifest.json",
    "face_landmark_68_model-shard1",
    "face_recognition_model-weights_manifest.json",
    "face_recognition_model-shard1",
    "face_recognition_model-shard2",
];

const DEST = path.join(__dirname, "..", "public", "vendor", "face-api", "models");
fs.mkdirSync(DEST, { recursive: true });

function get(url, redirects = 0) {
    return new Promise((resolve, reject) => {
        https.get(url, (res) => {
            if ([301, 302, 307, 308].includes(res.statusCode) && res.headers.location && redirects < 5) {
                res.resume();
                return resolve(get(res.headers.location, redirects + 1));
            }
            if (res.statusCode !== 200) {
                res.resume();
                return reject(new Error(`HTTP ${res.statusCode} for ${url}`));
            }
            const chunks = [];
            res.on("data", (c) => chunks.push(c));
            res.on("end", () => resolve(Buffer.concat(chunks)));
        }).on("error", reject);
    });
}

(async () => {
    for (const f of FILES) {
        process.stdout.write(`Fetching ${f} … `);
        try {
            const buf = await get(`${BASE}/${f}`);
            fs.writeFileSync(path.join(DEST, f), buf);
            console.log(`ok (${buf.length} bytes)`);
        } catch (e) {
            console.log(`FAILED: ${e.message}`);
            process.exitCode = 1;
        }
    }
    console.log(`\nDone → ${DEST}`);
})();
