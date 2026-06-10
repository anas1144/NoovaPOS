<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\Employee;
use App\Models\WebauthnCredential;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * WebAuthn (passkey / platform authenticator) attendance.
 *
 * Real on-device biometric — the laptop/phone fingerprint or face sensor does
 * the match locally; no external hardware. At registration the browser sends
 * the credential's public key (DER SPKI via getPublicKey()); at login the server
 * verifies the assertion signature with openssl. No third-party library needed.
 */
class WebAuthnController extends AppBaseController
{
    public function __construct(private readonly AttendanceService $attendance)
    {
    }

    // ── registration ─────────────────────────────────────────────────────────

    public function registerOptions(Request $request): JsonResponse
    {
        $data = $request->validate(['employee_id' => 'required|integer']);
        $employee = Employee::query()->find($data['employee_id']);
        if (! $employee) {
            return $this->sendError('Employee not found.', 404);
        }

        $challenge = random_bytes(32);
        $token = Str::random(40);
        Cache::put("wa_reg_{$token}", ['challenge' => $challenge, 'employee_id' => $employee->id], now()->addSeconds(120));

        return $this->sendResponse([
            'token'     => $token,
            'challenge' => $this->b64url($challenge),
            'rp_id'     => $request->getHost(),
            'rp_name'   => config('app.name', 'NoovaPOS'),
            'user'      => [
                'id'          => $this->b64url('emp-' . $employee->id),
                'name'        => $employee->employee_code ?: ('employee-' . $employee->id),
                'displayName' => $employee->full_name,
            ],
        ], 'WebAuthn registration options.');
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'         => 'required|string',
            'credential_id' => 'required|string',
            'public_key'    => 'required|string', // base64 DER SPKI
            'algorithm'     => 'nullable|integer',
            'label'         => 'nullable|string|max:120',
        ]);

        $cached = Cache::pull("wa_reg_{$data['token']}");
        if (! $cached) {
            return $this->sendError('Registration challenge expired. Try again.', 422);
        }

        $cred = WebauthnCredential::updateOrCreate(
            ['credential_id' => $data['credential_id']],
            [
                'employee_id' => $cached['employee_id'],
                'public_key'  => $data['public_key'],
                'algorithm'   => $data['algorithm'] ?? -7,
                'label'       => $data['label'] ?? null,
            ]
        );

        return $this->sendResponse(['id' => $cred->id], 'Device biometric registered.');
    }

    // ── login / verify ───────────────────────────────────────────────────────

    public function loginOptions(Request $request): JsonResponse
    {
        $challenge = random_bytes(32);
        $token = Str::random(40);
        Cache::put("wa_login_{$token}", ['challenge' => $challenge], now()->addSeconds(120));

        $allow = WebauthnCredential::query()->pluck('credential_id')
            ->map(fn ($id) => ['id' => $id, 'type' => 'public-key'])->values();

        return $this->sendResponse([
            'token'           => $token,
            'challenge'       => $this->b64url($challenge),
            'rp_id'           => $request->getHost(),
            'allowCredentials' => $allow,
        ], 'WebAuthn login options.');
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'              => 'required|string',
            'credential_id'      => 'required|string',
            'client_data_json'   => 'required|string', // base64
            'authenticator_data' => 'required|string', // base64
            'signature'          => 'required|string', // base64
        ]);

        $cached = Cache::pull("wa_login_{$data['token']}");
        if (! $cached) {
            return $this->sendError('Login challenge expired. Try again.', 422);
        }

        $clientDataRaw = base64_decode($data['client_data_json']);
        $authData      = base64_decode($data['authenticator_data']);
        $signature     = base64_decode($data['signature']);
        $clientData    = json_decode($clientDataRaw, true);

        if (! is_array($clientData) || ($clientData['type'] ?? '') !== 'webauthn.get') {
            return $this->sendError('Invalid client data.', 422);
        }
        // Challenge must match the one we issued (single-use).
        if (($clientData['challenge'] ?? '') !== $this->b64url($cached['challenge'])) {
            return $this->sendError('Challenge mismatch.', 422);
        }

        $cred = WebauthnCredential::query()->where('credential_id', $data['credential_id'])->first();
        if (! $cred) {
            return $this->sendError('Unknown credential.', 404);
        }

        // Signed data = authenticatorData || SHA-256(clientDataJSON).
        $signedData = $authData . hash('sha256', $clientDataRaw, true);
        $pem = $this->derToPem(base64_decode($cred->public_key));
        $ok = openssl_verify($signedData, $signature, $pem, OPENSSL_ALGO_SHA256);

        if ($ok !== 1) {
            return $this->sendError('Biometric verification failed.', 422);
        }

        $cred->update(['last_used_at' => now()]);

        $employee = Employee::query()->find($cred->employee_id);
        if (! $employee) {
            return $this->sendError('Employee not found.', 404);
        }

        return $this->sendResponse([
            'employee_id' => $employee->id,
            'status'      => $this->attendance->statusPayload($employee),
        ], 'Verified.');
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private function derToPem(string $der): string
    {
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }
}
