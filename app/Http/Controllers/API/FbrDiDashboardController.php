<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\FbrDiInvoice;
use App\Models\FbrErrorCode;
use App\Services\FbrDiQuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * FBR Digital Invoice dashboard widgets.
 */
class FbrDiDashboardController extends AppBaseController
{
    public function __construct(private readonly FbrDiQuotaService $quota)
    {
    }

    public function dashboard(Request $request): JsonResponse
    {
        $base = fn () => FbrDiInvoice::query();

        $byStatus = $base()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $totalTax = (float) $base()->sum('sales_tax');

        $recentErrors = FbrErrorCode::query()
            ->where('total_occurrences', '>', 0)
            ->orderByDesc('last_occurrence_at')
            ->take(5)
            ->get(['code', 'message', 'total_occurrences', 'last_occurrence_at']);

        return $this->sendResponse([
            'cards' => [
                'total_invoices' => (int) $base()->count(),
                'synced'         => (int) (($byStatus['synced'] ?? 0) + ($byStatus['accepted'] ?? 0)),
                'pending_sync'   => (int) (($byStatus['pending_sync'] ?? 0) + ($byStatus['syncing'] ?? 0)),
                'rejected'       => (int) ($byStatus['rejected'] ?? 0),
                'draft'          => (int) ($byStatus['draft'] ?? 0),
                'total_tax'      => round($totalTax, 2),
            ],
            'quota'         => $this->quota->summary($request->user()?->tenant_id),
            'recent_errors' => $recentErrors,
        ], 'FBR dashboard retrieved.');
    }
}
