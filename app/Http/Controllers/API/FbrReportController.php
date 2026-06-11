<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\FbrDiInvoice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * FBR Digital Invoice reports: sales, tax, sync status, and rejected invoices.
 */
class FbrReportController extends AppBaseController
{
    private function range(Request $request): array
    {
        $to = $request->filled('to') ? Carbon::parse($request->get('to')) : Carbon::today();
        $from = $request->filled('from') ? Carbon::parse($request->get('from')) : (clone $to)->startOfMonth();
        return [$from->startOfDay(), $to->endOfDay()];
    }

    private function base(Request $request)
    {
        [$from, $to] = $this->range($request);
        return FbrDiInvoice::query()
            ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
            ->when($request->filled('fbr_business_id'), fn ($q) => $q->where('fbr_business_id', $request->get('fbr_business_id')));
    }

    /** Sales report — accepted/synced invoices with value + tax totals. */
    public function sales(Request $request): JsonResponse
    {
        $rows = $this->base($request)
            ->whereIn('status', ['synced', 'accepted'])
            ->with('business:id,name')->orderByDesc('invoice_date')->get();

        return $this->sendResponse([
            'totals' => [
                'count'      => $rows->count(),
                'value_excl' => round($rows->sum('value_excl_tax'), 2),
                'sales_tax'  => round($rows->sum('sales_tax'), 2),
                'further_tax' => round($rows->sum('further_tax'), 2),
                'total'      => round($rows->sum('total_incl_tax'), 2),
            ],
            'rows' => $rows->map(fn ($i) => [
                'id' => $i->id, 'fbr_no' => $i->fbr_invoice_no, 'business' => $i->business?->name,
                'buyer' => $i->buyer_name, 'date' => $i->invoice_date?->toDateString(),
                'value_excl' => $i->value_excl_tax, 'sales_tax' => $i->sales_tax, 'total' => $i->total_incl_tax,
            ]),
        ], 'Sales report retrieved.');
    }

    /** Tax report — sales tax + further tax collected. */
    public function tax(Request $request): JsonResponse
    {
        $rows = $this->base($request)->whereIn('status', ['synced', 'accepted'])->get();

        return $this->sendResponse([
            'sales_tax'   => round($rows->sum('sales_tax'), 2),
            'further_tax' => round($rows->sum('further_tax'), 2),
            'total_tax'   => round($rows->sum('sales_tax') + $rows->sum('further_tax'), 2),
            'invoices'    => $rows->count(),
        ], 'Tax report retrieved.');
    }

    /** Sync report — status breakdown. */
    public function sync(Request $request): JsonResponse
    {
        $counts = $this->base($request)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        return $this->sendResponse([
            'draft'        => (int) ($counts['draft'] ?? 0),
            'pending_sync' => (int) ($counts['pending_sync'] ?? 0),
            'syncing'      => (int) ($counts['syncing'] ?? 0),
            'synced'       => (int) (($counts['synced'] ?? 0) + ($counts['accepted'] ?? 0)),
            'rejected'     => (int) ($counts['rejected'] ?? 0),
            'cancelled'    => (int) ($counts['cancelled'] ?? 0),
        ], 'Sync report retrieved.');
    }

    /** Rejected invoices report. */
    public function rejected(Request $request): JsonResponse
    {
        $rows = $this->base($request)->where('status', 'rejected')
            ->with('business:id,name')->orderByDesc('invoice_date')->get()
            ->map(fn ($i) => [
                'id' => $i->id, 'business' => $i->business?->name, 'buyer' => $i->buyer_name,
                'date' => $i->invoice_date?->toDateString(), 'total' => $i->total_incl_tax,
                'error' => $i->sync_error,
            ]);

        return $this->sendResponse($rows, 'Rejected invoices retrieved.');
    }
}
