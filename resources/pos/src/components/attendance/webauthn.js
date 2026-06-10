/**
 * WebAuthn (passkey / platform authenticator) helpers for attendance.
 *
 * Registration uses the device's own biometric (laptop/phone fingerprint or
 * face) to create a credential; the browser exposes the public key via
 * getPublicKey(), which we send to the server. Verification signs a server
 * challenge with that credential — the OS performs the biometric match locally.
 */
import apiConfig from "../../config/apiConfig";
import { apiBaseURL } from "../../constants";

export const webauthnSupported = () =>
    typeof window !== "undefined" && !!window.PublicKeyCredential && !!navigator.credentials;

const b64urlToBuf = (s) => {
    const pad = "=".repeat((4 - (s.length % 4)) % 4);
    const b64 = (s + pad).replace(/-/g, "+").replace(/_/g, "/");
    const bin = atob(b64);
    const buf = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i += 1) buf[i] = bin.charCodeAt(i);
    return buf.buffer;
};

const bufToB64 = (buf) => {
    const bytes = new Uint8Array(buf);
    let s = "";
    for (let i = 0; i < bytes.length; i += 1) s += String.fromCharCode(bytes[i]);
    return btoa(s);
};

const bufToB64url = (buf) => bufToB64(buf).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");

/** Register the current device's biometric for an employee. */
export async function registerDeviceBiometric(employeeId, label) {
    const { data } = await apiConfig.post(apiBaseURL.ATTENDANCE_WEBAUTHN_REGISTER_OPTIONS, { employee_id: employeeId });
    const opt = data?.data;

    const cred = await navigator.credentials.create({
        publicKey: {
            challenge: b64urlToBuf(opt.challenge),
            rp: { id: opt.rp_id, name: opt.rp_name },
            user: {
                id: b64urlToBuf(opt.user.id),
                name: opt.user.name,
                displayName: opt.user.displayName,
            },
            pubKeyCredParams: [
                { type: "public-key", alg: -7 },    // ES256
                { type: "public-key", alg: -257 },  // RS256
            ],
            authenticatorSelection: { userVerification: "required" },
            timeout: 60000,
            attestation: "none",
        },
    });

    const resp = cred.response;
    const publicKey = typeof resp.getPublicKey === "function" ? resp.getPublicKey() : null;
    const alg = typeof resp.getPublicKeyAlgorithm === "function" ? resp.getPublicKeyAlgorithm() : -7;
    if (!publicKey) {
        throw new Error("This browser can't export the device public key (needs a modern browser).");
    }

    return apiConfig.post(apiBaseURL.ATTENDANCE_WEBAUTHN_REGISTER, {
        token: opt.token,
        credential_id: bufToB64url(cred.rawId),
        public_key: bufToB64(publicKey),
        algorithm: alg,
        label: label || null,
    });
}

/** Verify with the device biometric; resolves to the matched status payload. */
export async function verifyDeviceBiometric() {
    const { data } = await apiConfig.post(apiBaseURL.ATTENDANCE_WEBAUTHN_LOGIN_OPTIONS, {});
    const opt = data?.data;

    const assertion = await navigator.credentials.get({
        publicKey: {
            challenge: b64urlToBuf(opt.challenge),
            rpId: opt.rp_id,
            allowCredentials: (opt.allowCredentials || []).map((c) => ({
                id: b64urlToBuf(c.id),
                type: "public-key",
            })),
            userVerification: "required",
            timeout: 60000,
        },
    });

    const r = assertion.response;
    const res = await apiConfig.post(apiBaseURL.ATTENDANCE_WEBAUTHN_VERIFY, {
        token: opt.token,
        credential_id: bufToB64url(assertion.rawId),
        client_data_json: bufToB64(r.clientDataJSON),
        authenticator_data: bufToB64(r.authenticatorData),
        signature: bufToB64(r.signature),
    });
    return res.data?.data; // { employee_id, status }
}
