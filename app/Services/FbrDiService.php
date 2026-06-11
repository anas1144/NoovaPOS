<?php

namespace App\Services;

use App\Models\FbrBusiness;
use App\Models\FbrDiInvoice;
use App\Models\FbrErrorCode;
use Illuminate\Support\Facades\Http;

/**
 * FBR Digital Invoice submission service (standalone module).
 *
 * Builds the FBR DI payload from an invoice, POSTs it to the configured endpoint
 * with the business's sandbox/production token, parses the response, and records
 * errors. Endpoints + payload follow the FBR DI integrator schema; they are
 * configurable via config/services or .env so a licensed integrator URL can be
 * dropped in without code changes.
 */
class FbrDiService
{
    /** Default FBR DI endpoints (overridable via env). */
    private function endpoint(string $environment): string
    {
        return $environment === 'production'
            ? env('FBR_DI_PROD_URL', 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata')
            : env('FBR_DI_SANDBOX_URL', 'https://gw.fbr.gov.pk/di_data/v1/di/postinvoicedata_sb');
    }

    /**
     * Submit an invoice to FBR. Returns a normalised result:
     *  ['ok'=>bool, 'status'=>accepted|rejected, 'fbr_no'=>?, 'qr'=>?, 'raw'=>array, 'error'=>?]
     */
    public function submit(FbrDiInvoice $invoice): array
    {
        $business = FbrBusiness::query()->find($invoice->fbr_business_id);
        if (! $business) {
            return $this->fail('Business not found for invoice.');
        }

        $env = $business->environment ?: 'sandbox';
        $token = $env === 'production' ? $business->production_token : $business->sandbox_token;
        if (! $token) {
            return $this->fail("No {$env} token configured for this business.");
        }

        $payload = $this->buildPayload($invoice, $business);

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->acceptJson()
                ->post($this->endpoint($env), $payload);

            $json = $response->json() ?? [];
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        // FBR DI returns validationResponse.statusCode "00" on success +
        // an invoiceNumber. We normalise around that, tolerating shape drift.
        $vr = $json['validationResponse'] ?? $json['ValidationResponse'] ?? [];
        $statusCode = (string) ($vr['statusCode'] ?? $vr['StatusCode'] ?? '');
        $fbrNo = $json['invoiceNumber'] ?? $json['InvoiceNumber'] ?? null;
        $accepted = ($statusCode === '00') || ($fbrNo && $statusCode === '');

        if (! $accepted) {
            $msg = $vr['error'] ?? $vr['Error'] ?? ($json['message'] ?? 'FBR rejected the invoice.');
            $this->recordError((string) ($vr['errorCode'] ?? $vr['statusCode'] ?? 'FBR'), $msg);
            return [
                'ok' => true, 'status' => FbrDiInvoice::STATUS_REJECTED,
                'fbr_no' => null, 'qr' => null, 'raw' => $json, 'error' => $msg,
            ];
        }

        return [
            'ok'     => true,
            'status' => FbrDiInvoice::STATUS_ACCEPTED,
            'fbr_no' => $fbrNo,
            'qr'     => $fbrNo, // QR encodes the FBR invoice number
            'raw'    => $json,
            'error'  => null,
        ];
    }

    private function buildPayload(FbrDiInvoice $invoice, FbrBusiness $business): array
    {
        return [
            'invoiceType'           => $invoice->invoice_type === 'sale' ? 'Sale Invoice' : ucwords(str_replace('_', ' ', $invoice->invoice_type)),
            'invoiceDate'           => optional($invoice->invoice_date)->format('Y-m-d'),
            'sellerNTNCNIC'         => $business->ntn_cnic,
            'sellerBusinessName'    => $business->name,
            'sellerProvince'        => $business->province,
            'sellerAddress'         => $business->address,
            'buyerNTNCNIC'          => $invoice->buyer_ntn_cnic,
            'buyerBusinessName'     => $invoice->buyer_name,
            'buyerProvince'         => $invoice->buyer_province,
            'buyerAddress'          => $invoice->buyer_address,
            'buyerRegistrationType' => ucfirst($invoice->buyer_registration_type),
            'invoiceRefNo'          => $invoice->ref_inv_no,
            'items'                 => $invoice->items->map(fn ($it) => [
                'hsCode'             => $it->hs_code,
                'productDescription' => $it->description,
                'rate'               => (float) $it->rate_per_unit,
                'uoM'                => $it->uom,
                'quantity'           => (float) $it->quantity,
                'valueSalesExcludingST' => (float) $it->value_excl_tax,
                'salesTaxApplicable' => (float) $it->value_of_sales_tax,
                'taxRate'            => (float) $it->rate_of_sales_tax,
                'totalValues'        => (float) $it->value_incl_tax,
            ])->values()->all(),
        ];
    }

    /** Increment the occurrence counter for an error code. */
    public function recordError(string $code, string $message): void
    {
        $row = FbrErrorCode::query()->firstOrNew(['code' => $code]);
        $row->message = $message ?: $row->message;
        $row->total_occurrences = (int) $row->total_occurrences + 1;
        $row->last_occurrence_at = now();
        $row->save();
    }

    private function fail(string $error): array
    {
        return ['ok' => false, 'status' => FbrDiInvoice::STATUS_REJECTED, 'fbr_no' => null, 'qr' => null, 'raw' => [], 'error' => $error];
    }
}
