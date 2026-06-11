<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\AuditLog;
use App\Models\FbrDiInvoice;
use App\Models\FbrDiInvoiceItem;
use App\Services\FbrDiQuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * FBR Digital Invoice CRUD. Invoices are created as Draft; sync to FBR is a
 * separate, permission-gated action (see FbrDiSyncController). A synced/accepted/
 * rejected invoice is locked from edit/delete.
 */
class FbrDiInvoiceController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $rows = FbrDiInvoice::query()
            ->with('business:id,name,ntn_cnic')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->when($request->filled('fbr_business_id'), fn ($q) => $q->where('fbr_business_id', $request->get('fbr_business_id')))
            ->when($request->filled('search'), fn ($q) => $q->where(function ($w) use ($request) {
                $s = $request->get('search');
                $w->where('local_no', 'like', "%{$s}%")
                  ->orWhere('fbr_invoice_no', 'like', "%{$s}%")
                  ->orWhere('buyer_name', 'like', "%{$s}%");
            }))
            ->orderByDesc('id')
            ->paginate((int) $request->get('page_size', 20));

        return $this->sendResponse($rows, 'FBR invoices retrieved.');
    }

    public function show($id): JsonResponse
    {
        $invoice = FbrDiInvoice::query()->with(['business', 'items'])->findOrFail($id);
        return $this->sendResponse($invoice, 'FBR invoice retrieved.');
    }

    public function store(Request $request, FbrDiQuotaService $quota): JsonResponse
    {
        if (! $quota->canCreateInvoice($request->user()?->tenant_id)) {
            return $this->sendError('Monthly invoice quota reached for your plan. Contact the administrator to raise the limit.', 422);
        }

        $data = $this->validatePayload($request);

        $invoice = DB::transaction(function () use ($data, $request) {
            $invoice = new FbrDiInvoice();
            $invoice->fill($this->invoiceAttributes($data));
            $invoice->status = FbrDiInvoice::STATUS_DRAFT;
            $invoice->local_no = $data['local_no'] ?? ('DRAFT-' . strtoupper(uniqid()));
            $invoice->created_by = $request->user()?->id;
            $invoice->save();

            $this->syncItems($invoice, $data['items']);
            $this->recalculate($invoice);

            return $invoice;
        });

        $this->audit($request, 'fbr_invoice.created', $invoice);

        return $this->sendResponse($invoice->load('items'), 'Draft invoice created.');
    }

    public function update(Request $request, $id): JsonResponse
    {
        $invoice = FbrDiInvoice::query()->findOrFail($id);
        if ($invoice->isLocked()) {
            return $this->sendError('A synced/accepted/rejected invoice cannot be edited.', 422);
        }

        $data = $this->validatePayload($request);

        DB::transaction(function () use ($invoice, $data) {
            $invoice->fill($this->invoiceAttributes($data))->save();
            FbrDiInvoiceItem::query()->where('fbr_di_invoice_id', $invoice->id)->delete();
            $this->syncItems($invoice, $data['items']);
            $this->recalculate($invoice);
        });

        $this->audit($request, 'fbr_invoice.updated', $invoice);

        return $this->sendResponse($invoice->load('items'), 'Invoice updated.');
    }

    public function destroy($id): JsonResponse
    {
        $invoice = FbrDiInvoice::query()->findOrFail($id);

        // Only an un-synced Draft may be deleted.
        if ($invoice->status !== FbrDiInvoice::STATUS_DRAFT) {
            return $this->sendError('Only an un-synced Draft invoice can be deleted.', 422);
        }

        FbrDiInvoiceItem::query()->where('fbr_di_invoice_id', $invoice->id)->delete();
        $this->audit(request(), 'fbr_invoice.deleted', $invoice);
        $invoice->delete();

        return $this->sendSuccess('Draft invoice deleted.');
    }

    /** Write an audit-log entry for an FBR-DI invoice action. */
    private function audit(Request $request, string $event, FbrDiInvoice $invoice): void
    {
        try {
            AuditLog::create([
                'tenant_id'      => $request->user()?->tenant_id,
                'actor_id'       => $request->user()?->id,
                'event'          => $event,
                'auditable_type' => FbrDiInvoice::class,
                'auditable_id'   => $invoice->id,
                'ip_address'     => $request->ip(),
                'user_agent'     => substr((string) $request->userAgent(), 0, 255),
                'new_values'     => ['status' => $invoice->status, 'total' => $invoice->total_incl_tax],
                'created_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            // Audit must never block the action.
        }
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'fbr_business_id'          => 'required|integer',
            'local_no'                 => 'nullable|string|max:40',
            'ref_inv_no'               => 'nullable|string|max:80',
            'invoice_type'             => 'nullable|in:sale,credit_note,debit_note',
            'invoice_date'             => 'required|date',
            'buyer_ntn_cnic'           => 'nullable|string|max:20',
            'buyer_name'               => 'nullable|string|max:191',
            'buyer_registration_type'  => 'nullable|in:registered,unregistered',
            'buyer_province'           => 'nullable|string|max:60',
            'buyer_address'            => 'nullable|string|max:255',
            'further_tax'              => 'nullable|numeric|min:0',
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'nullable|integer',
            'items.*.hs_code'          => 'nullable|string|max:20',
            'items.*.description'      => 'required|string|max:255',
            'items.*.uom'              => 'nullable|string|max:30',
            'items.*.rate_per_unit'    => 'required|numeric|min:0',
            'items.*.quantity'         => 'required|numeric|min:0',
            'items.*.rate_of_sales_tax' => 'nullable|numeric|min:0|max:100',
        ]);
    }

    private function invoiceAttributes(array $data): array
    {
        return [
            'fbr_business_id'         => $data['fbr_business_id'],
            'ref_inv_no'              => $data['ref_inv_no'] ?? null,
            'invoice_type'            => $data['invoice_type'] ?? 'sale',
            'invoice_date'            => $data['invoice_date'],
            'buyer_ntn_cnic'          => $data['buyer_ntn_cnic'] ?? null,
            'buyer_name'              => $data['buyer_name'] ?? null,
            'buyer_registration_type' => $data['buyer_registration_type'] ?? 'unregistered',
            'buyer_province'          => $data['buyer_province'] ?? null,
            'buyer_address'           => $data['buyer_address'] ?? null,
            'further_tax'             => $data['further_tax'] ?? 0,
        ];
    }

    private function syncItems(FbrDiInvoice $invoice, array $items): void
    {
        $sr = 1;
        foreach ($items as $row) {
            $qty   = (float) $row['quantity'];
            $rate  = (float) $row['rate_per_unit'];
            $taxPc = (float) ($row['rate_of_sales_tax'] ?? 0);

            $excl = round($qty * $rate, 2);
            $tax  = round($excl * $taxPc / 100, 2);

            FbrDiInvoiceItem::create([
                'fbr_di_invoice_id'  => $invoice->id,
                'product_id'         => $row['product_id'] ?? null,
                'sr_no'              => $sr++,
                'hs_code'            => $row['hs_code'] ?? null,
                'description'        => $row['description'],
                'uom'                => $row['uom'] ?? null,
                'rate_per_unit'      => $rate,
                'quantity'           => $qty,
                'value_excl_tax'     => $excl,
                'rate_of_sales_tax'  => $taxPc,
                'value_of_sales_tax' => $tax,
                'value_incl_tax'     => round($excl + $tax, 2),
            ]);
        }
    }

    private function recalculate(FbrDiInvoice $invoice): void
    {
        $items = $invoice->items()->get();
        $excl = round($items->sum('value_excl_tax'), 2);
        $tax  = round($items->sum('value_of_sales_tax'), 2);
        $further = (float) $invoice->further_tax;

        $invoice->value_excl_tax = $excl;
        $invoice->sales_tax      = $tax;
        $invoice->total_incl_tax = round($excl + $tax + $further, 2);
        $invoice->save();
    }
}
