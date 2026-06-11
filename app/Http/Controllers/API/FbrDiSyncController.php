<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Jobs\SyncFbrDiInvoiceJob;
use App\Models\AuditLog;
use App\Models\FbrDiInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlled FBR sync. Requires the fbr_invoice_sync permission. Queues the
 * submission; the invoice moves Draft → pending_sync → (job) syncing →
 * accepted | rejected.
 */
class FbrDiSyncController extends AppBaseController
{
    public function sync(Request $request, $id): JsonResponse
    {
        if (! $request->user()?->can('fbr_invoice_sync')) {
            return $this->sendError('You do not have permission to sync invoices.', 403);
        }

        $invoice = FbrDiInvoice::query()->findOrFail($id);

        if ($invoice->isLocked() && $invoice->status !== FbrDiInvoice::STATUS_REJECTED) {
            return $this->sendError('This invoice is already finalised.', 422);
        }
        if ($invoice->items()->count() === 0) {
            return $this->sendError('Add items before syncing.', 422);
        }

        $invoice->update(['status' => FbrDiInvoice::STATUS_PENDING_SYNC, 'sync_error' => null]);
        SyncFbrDiInvoiceJob::dispatch($invoice->id);

        try {
            AuditLog::create([
                'tenant_id' => $request->user()?->tenant_id,
                'actor_id'  => $request->user()?->id,
                'event'     => 'fbr_invoice.synced',
                'auditable_type' => FbrDiInvoice::class,
                'auditable_id'   => $invoice->id,
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) { /* non-blocking */ }

        return $this->sendResponse(
            ['id' => $invoice->id, 'status' => $invoice->status],
            'Invoice queued for FBR sync.'
        );
    }

    public function bulkSync(Request $request): JsonResponse
    {
        if (! $request->user()?->can('fbr_invoice_sync')) {
            return $this->sendError('You do not have permission to sync invoices.', 403);
        }
        $ids = (array) $request->input('ids', []);
        $queued = 0;

        FbrDiInvoice::query()->whereIn('id', $ids)
            ->whereIn('status', [FbrDiInvoice::STATUS_DRAFT, FbrDiInvoice::STATUS_REJECTED, FbrDiInvoice::STATUS_PENDING_SYNC])
            ->get()->each(function ($invoice) use (&$queued) {
                if ($invoice->items()->count() === 0) {
                    return;
                }
                $invoice->update(['status' => FbrDiInvoice::STATUS_PENDING_SYNC, 'sync_error' => null]);
                SyncFbrDiInvoiceJob::dispatch($invoice->id);
                $queued++;
            });

        return $this->sendResponse(['queued' => $queued], "{$queued} invoice(s) queued for FBR sync.");
    }
}
