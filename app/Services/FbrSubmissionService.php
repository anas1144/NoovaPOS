<?php

namespace App\Services;

use App\Models\FbrInvoice;
use App\Models\FbrProfile;
use App\Models\Sale;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * FBR POS Invoice Submission Service
 * ─────────────────────────────────────────────────────────────────────────────
 * Real FBR POS API endpoints (Pakistan):
 *   fbr_req_type 0 → https://esp.fbr.gov.pk:8244/FBR/v1/api/Live/PostData
 *   fbr_req_type 1 → https://gw.fbr.gov.pk/imsp/v1/api/Live/PostData
 *   fbr_req_type 2 → sandbox / FBR disabled (default for non-PK shops)
 *
 * FBR response shape:
 *   { "InvoiceNumber": "...", "Code": 0, "Response": "Invoice Saved Successfully" }
 *
 * ── Print-first, sync-later strategy ─────────────────────────────────────────
 * The internal USIN (reference_code / order_number) is generated before the
 * FBR call. The receipt prints the USIN immediately.
 *
 *   Success → FBR returns InvoiceNumber → receipt shows FBR number.
 *   Failure → receipt shows USIN + "FBR PENDING". Cashier retries from
 *              the FBR queue dashboard; SMS/WhatsApp can notify customer later.
 */
class FbrSubmissionService
{
    private const ENDPOINT_ESP = 'https://esp.fbr.gov.pk:8244/FBR/v1/api/Live/PostData';
    private const ENDPOINT_GW  = 'https://gw.fbr.gov.pk/imsp/v1/api/Live/PostData';

    // FBR line item InvoiceType codes
    public const INV_NORMAL         = 1;
    public const INV_NORMAL_RETURN  = 3;
    public const INV_3RD_SCHEDULE   = 11;
    public const INV_3RD_RETURN     = 12;

    /**
     * Called right after a sale is committed to DB.
     * Returns FbrInvoice (status=synced or failed), or null if FBR not active.
     */
    public function submitForSale(Sale $sale, ?Shop $shop = null): ?FbrInvoice
    {
        $shop = $shop ?? Shop::find($sale->shop_id ?? null);

        if (!$shop || !$this->isFbrActive($shop)) {
            return null;
        }

        $payload = $this->buildPayload($sale, $shop);
        if (!$payload) {
            return null;
        }

        $fbrInvoice = FbrInvoice::create([
            'tenant_id'          => $sale->tenant_id ?? currentTenantId(),
            'store_id'           => $sale->warehouse_id ?? null,
            'shop_id'            => $shop->id,
            'fbr_profile_id'     => $this->getProfileId($shop),
            'sale_id'            => $sale->id,
            'invoice_no'         => $sale->reference_code ?? ('SALE-' . $sale->id),
            'mode'               => 'production',
            'status'             => FbrInvoice::STATUS_QUEUED,
            'total_amount'       => (float)($sale->grand_total ?? 0),
            'tax_amount'         => (float)($sale->tax_amount ?? 0),
            'request_payload'    => $payload,
            'attempt_count'      => 0,
            'fbr_pos_service_fee'=> (bool)env('FBR_POS_SERVICE_FEE_ENABLED', false),
        ]);

        // Link sale → fbr_invoice immediately so receipt can reference it
        $sale->update(['fbr_invoice_id' => $fbrInvoice->id]);

        return $this->submit($fbrInvoice, $shop);
    }

    /**
     * Submit a queued/failed FbrInvoice to FBR API (also used for retries).
     */
    public function submit(FbrInvoice $invoice, ?Shop $shop = null): FbrInvoice
    {
        $invoice->update([
            'status'        => FbrInvoice::STATUS_SENDING,
            'attempt_count' => $invoice->attempt_count + 1,
            'submitted_at'  => now(),
        ]);

        $shop = $shop ?? Shop::find($invoice->shop_id);

        try {
            $response = $this->callFbrApi($invoice, $shop);

            $code      = (int)($response['Code'] ?? $response['code'] ?? -1);
            $fbrNumber = $response['InvoiceNumber'] ?? $response['invoiceNumber'] ?? null;

            if ($code === 0 && $fbrNumber) {
                $invoice->update([
                    'status'             => FbrInvoice::STATUS_SYNCED,
                    'fbr_invoice_number' => $fbrNumber,
                    'fbr_invoice_no'     => $fbrNumber,
                    'fbr_code'           => (string)$code,
                    'fbr_response'       => $response['Response'] ?? 'Accepted',
                    'fbr_qr_payload'     => $this->buildQrPayload($fbrNumber, $invoice),
                    'response_payload'   => $response,
                    'synced_at'          => now(),
                    'error_message'      => null,
                ]);
            } else {
                throw new \RuntimeException(
                    'FBR rejected. Code: ' . $code .
                    ' — ' . ($response['Response'] ?? json_encode($response))
                );
            }
        } catch (\Throwable $e) {
            $invoice->update([
                'status'        => FbrInvoice::STATUS_FAILED,
                'fbr_code'      => '-1',
                'fbr_response'  => substr($e->getMessage(), 0, 255),
                'failed_at'     => now(),
                'error_message' => substr($e->getMessage(), 0, 1000),
            ]);

            Log::warning('FBR POS submission failed', [
                'fbr_invoice_id' => $invoice->id,
                'sale_id'        => $invoice->sale_id,
                'shop_id'        => $invoice->shop_id,
                'error'          => $e->getMessage(),
            ]);
        }

        return $invoice->refresh();
    }

    public function retry(FbrInvoice $invoice): FbrInvoice
    {
        $invoice->update(['status' => FbrInvoice::STATUS_RETRYING, 'error_message' => null]);
        return $this->submit($invoice);
    }

    public function processQueue(int $limit = 50): array
    {
        $sent = $failed = 0;

        $pending = FbrInvoice::withoutGlobalScope('tenant')
            ->whereIn('status', [FbrInvoice::STATUS_QUEUED, FbrInvoice::STATUS_RETRYING])
            ->orderBy('id')->limit($limit)->get();

        foreach ($pending as $row) {
            $this->submit($row)->isSynced() ? $sent++ : $failed++;
        }

        return ['processed' => $pending->count(), 'sent' => $sent, 'failed' => $failed];
    }

    // ── Payload builders ──────────────────────────────────────────────────────

    private function buildPayload(Sale $sale, Shop $shop): ?array
    {
        $items = $this->buildItems($sale);
        if (empty($items)) {
            return null;
        }

        $customer    = $sale->customer;
        $usin        = $sale->reference_code ?? ('SALE-' . $sale->id);
        $paymentMode = $this->mapPaymentMode($sale->payment_type ?? 'cash');

        $gstPct      = (float)($sale->tax_rate ?? config('app.gst_percentage', 17));
        $grandTotal  = (float)($sale->grand_total ?? 0);
        $taxAmount   = (float)($sale->tax_amount ?? 0);
        $saleValue   = $grandTotal / (100 + $gstPct) * 100;
        $discount    = (float)($sale->discount ?? 0);

        return [
            'InvoiceNumber'    => $usin,
            'POSID'            => $shop->fbr_pos_id,
            'USIN'             => $usin,
            'DateTime'         => Carbon::parse($sale->date ?? now())->format('Y-m-d H:i:s'),
            'BuyerNTN'         => $customer->ntn_number ?? '',
            'BuyerCNIC'        => $customer->cnic ?? '',
            'BuyerName'        => $customer->name ?? 'WALKING',
            'BuyerPhoneNumber' => $customer->phone ?? $customer->mobile ?? '0000-0000000',
            'TotalBillAmount'  => abs(round($grandTotal, 2)),
            'TotalQuantity'    => abs(round($sale->saleItems->sum('quantity'), 1)),
            'TotalSaleValue'   => abs(round($saleValue, 2)),
            'TotalTaxCharged'  => abs(round($taxAmount, 1)),
            'Discount'         => abs(round($discount, 1)),
            'FurtherTax'       => 0.0,
            'PaymentMode'      => $paymentMode,
            'RefUSIN'          => $sale->return_reference ?? null,
            'InvoiceType'      => (int)($sale->fbr_invoice_type ?? self::INV_NORMAL),
            'Items'            => $items,
        ];
    }

    private function buildItems(Sale $sale): array
    {
        $items      = [];
        $gstPct     = (float)($sale->tax_rate ?? config('app.gst_percentage', 17));
        $gstApply   = (int)($sale->gst_apply ?? 2); // 1=included 2=excluded

        foreach ($sale->saleItems as $line) {
            $product      = $line->product;
            $qty          = (float)($line->quantity ?? 1);
            $unitPrice    = (float)($line->unit_price ?? $line->product_price ?? 0);
            $lineDiscount = (float)($line->discount ?? 0) * abs($qty);

            // Compute exclusive base and tax
            $saleValue  = ($unitPrice / (100 + $gstPct)) * 100 * $qty - $lineDiscount;
            $taxCharged = ($saleValue * $gstPct) / 100;
            $totalAmount = $saleValue + $taxCharged;

            // Per-line invoice type
            $lineType = ($qty < 0)
                ? ($gstApply === 1 ? self::INV_3RD_RETURN   : self::INV_NORMAL_RETURN)
                : ($gstApply === 1 ? self::INV_3RD_SCHEDULE : self::INV_NORMAL);

            $items[] = [
                'ItemCode'    => $line->product_barcode ?? ($product->barcode ?? ''),
                'ItemName'    => $product->name ?? ('Item-' . $line->product_id),
                'Quantity'    => abs($qty),
                'PCTCode'     => $product->fbr_pct_code ?? '',
                'TaxRate'     => round($gstPct, 1),
                'SaleValue'   => round(abs($saleValue)),
                'TotalAmount' => round(abs($totalAmount)),
                'TaxCharged'  => round(abs($taxCharged)),
                'Discount'    => round(abs($lineDiscount)),
                'FurtherTax'  => 0.0,
                'InvoiceType' => $lineType,
                'RefUSIN'     => $sale->return_reference ?? null,
            ];
        }

        return $items;
    }

    // ── HTTP call ─────────────────────────────────────────────────────────────

    /**
     * POST to FBR using cURL (matches the original implementation).
     * SSL verification disabled — FBR servers use self-signed/non-standard certs.
     */
    private function callFbrApi(FbrInvoice $invoice, ?Shop $shop): array
    {
        $reqType = (int)($shop?->fbr_req_type ?? 2);
        $token   = $shop?->fbr_auth_token ?? '';
        $payload = json_encode($invoice->request_payload);

        // Sandbox mode — return simulated success (dev/staging only)
        if ($reqType === 2 || empty($token)) {
            return [
                'InvoiceNumber' => 'SB-' . $invoice->invoice_no,
                'Code'          => 0,
                'Response'      => 'Sandbox — not submitted to FBR',
            ];
        }

        $endpoint = ($reqType === 0) ? self::ENDPOINT_ESP : self::ENDPOINT_GW;

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
            'Accept: application/json',
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HEADER         => 0,
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno || $body === false) {
            throw new \RuntimeException("cURL ({$errno}): {$error}");
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('FBR non-JSON response: ' . substr($body, 0, 300));
        }

        return $decoded;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isFbrActive(Shop $shop): bool
    {
        return $shop->country === 'PK'
            && in_array((int)$shop->fbr_req_type, [0, 1], true)
            && !empty($shop->fbr_auth_token)
            && !empty($shop->fbr_pos_id);
    }

    private function buildQrPayload(string $fbrNumber, FbrInvoice $invoice): string
    {
        return base64_encode(json_encode([
            'fbr_invoice_no' => $fbrNumber,
            'usin'           => $invoice->invoice_no,
            'total'          => (float)$invoice->total_amount,
            'tax'            => (float)$invoice->tax_amount,
            'ts'             => now()->toIso8601String(),
        ]));
    }

    private function mapPaymentMode(string $paymentType): int
    {
        if (str_contains(strtoupper($paymentType), 'AND')) {
            return 5; // Split payment
        }
        return match ($paymentType) {
            'card'   => 2,
            'cheque' => 6,
            'credit' => 100,
            default  => 1, // cash
        };
    }

    private function getProfileId(Shop $shop): int
    {
        $profile = FbrProfile::firstOrCreate(
            ['store_id' => $shop->store_id, 'tenant_id' => $shop->tenant_id],
            [
                'business_name'    => $shop->name,
                'ntn'              => $shop->fbr_ntn ?? 'N/A',
                'strn'             => $shop->fbr_strn,
                'pos_id'           => $shop->fbr_pos_id,
                'production_token' => $shop->fbr_auth_token,
                'mode'             => 'production',
                'enabled'          => true,
            ]
        );
        return $profile->id;
    }
}
