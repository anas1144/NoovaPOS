<?php

namespace App\Services;

use App\Models\FbrBusiness;
use App\Models\FbrDiInvoice;
use App\Models\FbrDiLimit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * FBR-DI plan quota: monthly invoice limit + businesses limit per Company.
 */
class FbrDiQuotaService
{
    private function tid(?string $tenantId): ?string
    {
        return $tenantId ?: (Auth::check() ? Auth::user()->tenant_id : null);
    }

    public function limit(?string $tenantId = null): ?FbrDiLimit
    {
        $tid = $this->tid($tenantId);
        return $tid ? FbrDiLimit::query()->where('tenant_id', $tid)->first() : null;
    }

    public function monthlyUsage(?string $tenantId = null): int
    {
        $tid = $this->tid($tenantId);
        if (! $tid) {
            return 0;
        }
        return FbrDiInvoice::withoutGlobalScope('tenant')
            ->where('tenant_id', $tid)
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->count();
    }

    public function businessesCount(?string $tenantId = null): int
    {
        $tid = $this->tid($tenantId);
        if (! $tid) {
            return 0;
        }
        return FbrBusiness::withoutGlobalScope('tenant')->where('tenant_id', $tid)->count();
    }

    /** Remaining monthly invoice quota (null = unlimited). */
    public function remaining(?string $tenantId = null): ?int
    {
        $limit = $this->limit($tenantId);
        $max = $limit?->monthly_invoice_limit;
        if ($max === null) {
            return null; // unlimited
        }
        return max(0, $max - $this->monthlyUsage($tenantId));
    }

    public function canCreateInvoice(?string $tenantId = null): bool
    {
        $remaining = $this->remaining($tenantId);
        return $remaining === null || $remaining > 0;
    }

    public function canCreateBusiness(?string $tenantId = null): bool
    {
        $limit = $this->limit($tenantId);
        $max = $limit?->businesses_limit;
        return $max === null || $this->businessesCount($tenantId) < $max;
    }

    public function summary(?string $tenantId = null): array
    {
        $limit = $this->limit($tenantId);
        $usage = $this->monthlyUsage($tenantId);
        $max = $limit?->monthly_invoice_limit;

        return [
            'plan_type'             => $limit?->plan_type ?? 'single',
            'monthly_invoice_limit' => $max,
            'monthly_usage'         => $usage,
            'remaining'             => $max === null ? null : max(0, $max - $usage),
            'consumption_percent'   => $max ? min(100, (int) round(($usage / $max) * 100)) : 0,
            'businesses_limit'      => $limit?->businesses_limit,
            'businesses_count'      => $this->businessesCount($tenantId),
        ];
    }
}
