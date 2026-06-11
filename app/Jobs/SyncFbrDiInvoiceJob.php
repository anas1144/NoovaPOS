<?php

namespace App\Jobs;

use App\Models\FbrDiInvoice;
use App\Services\FbrDiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Background sync of an FBR Digital Invoice to FBR. Runs on the `fbr` queue
 * (see config/horizon.php). Transitions: syncing → accepted | rejected.
 */
class SyncFbrDiInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(public int $invoiceId)
    {
        $this->onQueue('fbr');
    }

    public function handle(FbrDiService $fbr): void
    {
        $invoice = FbrDiInvoice::query()->with('items')->find($this->invoiceId);
        if (! $invoice || $invoice->isLocked()) {
            return; // gone or already finalised
        }

        $invoice->update(['status' => FbrDiInvoice::STATUS_SYNCING]);

        $result = $fbr->submit($invoice);

        $invoice->update([
            'status'         => $result['ok'] ? $result['status'] : FbrDiInvoice::STATUS_REJECTED,
            'fbr_invoice_no' => $result['fbr_no'],
            'qr_payload'     => $result['qr'],
            'sync_response'  => json_encode($result['raw']),
            'sync_error'     => $result['error'],
            'synced_at'      => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        FbrDiInvoice::query()->where('id', $this->invoiceId)->update([
            'status'     => FbrDiInvoice::STATUS_REJECTED,
            'sync_error' => 'Sync job failed: ' . $e->getMessage(),
        ]);
    }
}
