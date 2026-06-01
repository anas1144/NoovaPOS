<?php

namespace App\Services;

use App\Models\FbrInvoice;
use App\Models\FbrProfile;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * FBR Digital Invoicing submission.
 *
 * - For sandbox without a real licensed-integrator endpoint we generate a
 *   plausible response so the rest of the flow (queue → synced, QR print)
 *   works end-to-end.
 * - When `FBR_API_ENDPOINT` env is set the service POSTs the payload there
 *   with the profile's token and stores the real response.
 */
class FbrSubmissionService
{
    public function queueForSale(Sale $sale): ?FbrInvoice
    {
        $profile = FbrProfile::query()
            ->where('tenant_id', $sale->tenant_id ?? currentTenantId())
            ->where('enabled', true)
            ->when($sale->warehouse_id ?? null, fn($q, $w) => $q)
            ->orderByDesc('id')
            ->first();

        if (!$profile) {
            return null;
        }

        return FbrInvoice::create([
            'tenant_id' => $sale->tenant_id ?? currentTenantId(),
            'store_id' => $sale->warehouse_id ?? null,
            'fbr_profile_id' => $profile->id,
            'sale_id' => $sale->id,
            'invoice_no' => $sale->reference_code ?? 'SALE-' . $sale->id,
            'mode' => $profile->mode ?? 'sandbox',
            'status' => FbrInvoice::STATUS_QUEUED,
            'total_amount' => (float) ($sale->grand_total ?? 0),
            'tax_amount' => (float) ($sale->tax_amount ?? 0),
            'request_payload' => $this->buildPayload($profile, $sale),
            'attempt_count' => 0,
        ]);
    }

    public function submit(FbrInvoice $invoice): FbrInvoice
    {
        $invoice->update([
            'status' => FbrInvoice::STATUS_SENDING,
            'attempt_count' => $invoice->attempt_count + 1,
            'submitted_at' => now(),
        ]);

        try {
            $response = $this->callFbr($invoice);

            $invoice->update([
                'status' => FbrInvoice::STATUS_SYNCED,
                'fbr_invoice_no' => $response['invoice_number'] ?? null,
                'fbr_uuid' => $response['uuid'] ?? Str::uuid()->toString(),
                'fbr_qr_payload' => $response['qr_payload'] ?? $this->generateQrPayload($invoice),
                'response_payload' => $response,
                'synced_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $invoice->update([
                'status' => FbrInvoice::STATUS_FAILED,
                'failed_at' => now(),
                'error_message' => substr($e->getMessage(), 0, 1000),
            ]);
            Log::warning('FBR submission failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $invoice->refresh();
    }

    public function retry(FbrInvoice $invoice): FbrInvoice
    {
        $invoice->update([
            'status' => FbrInvoice::STATUS_RETRYING,
            'error_message' => null,
        ]);
        return $this->submit($invoice);
    }

    /**
     * Run the pending queue.
     */
    public function processQueue(int $limit = 50): array
    {
        $sent = 0;
        $failed = 0;
        $pending = FbrInvoice::withoutGlobalScope('tenant')
            ->whereIn('status', [FbrInvoice::STATUS_QUEUED, FbrInvoice::STATUS_RETRYING])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($pending as $row) {
            $result = $this->submit($row);
            if ($result->status === FbrInvoice::STATUS_SYNCED) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return [
            'processed' => $pending->count(),
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    private function callFbr(FbrInvoice $invoice): array
    {
        $endpoint = env('FBR_API_ENDPOINT');
        $profile = $invoice->profile;
        $token = $profile?->mode === 'production'
            ? $profile?->production_token
            : $profile?->sandbox_token;

        if ($endpoint && $token) {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(20)
                ->post($endpoint, $invoice->request_payload);

            if (!$response->successful()) {
                throw new \RuntimeException(
                    'FBR endpoint returned ' . $response->status() . ': ' . $response->body()
                );
            }
            return $response->json() ?? [];
        }

        // Stub response when no real endpoint is configured. This keeps the
        // dashboard/printout flow exercisable in sandbox/dev.
        return [
            'invoice_number' => 'FBR-' . Carbon::now()->format('YmdHis') . '-' . str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT),
            'uuid' => Str::uuid()->toString(),
            'status' => 'OK',
            'qr_payload' => $this->generateQrPayload($invoice),
        ];
    }

    private function buildPayload(FbrProfile $profile, Sale $sale): array
    {
        return [
            'pos_id' => $profile->pos_id,
            'ntn' => $profile->ntn,
            'strn' => $profile->strn,
            'province' => $profile->province,
            'business_activity' => $profile->business_activity,
            'invoice_no' => $sale->reference_code ?? 'SALE-' . $sale->id,
            'invoice_date' => optional($sale->date)->toDateString() ?? Carbon::now()->toDateString(),
            'total_amount' => (float) ($sale->grand_total ?? 0),
            'tax_amount' => (float) ($sale->tax_amount ?? 0),
        ];
    }

    private function generateQrPayload(FbrInvoice $invoice): string
    {
        return base64_encode(json_encode([
            'invoice_no' => $invoice->fbr_invoice_no ?? $invoice->invoice_no,
            'uuid' => $invoice->fbr_uuid ?? Str::uuid()->toString(),
            'total' => (float) $invoice->total_amount,
            'tax' => (float) $invoice->tax_amount,
            'ts' => now()->toIso8601String(),
        ]));
    }
}
