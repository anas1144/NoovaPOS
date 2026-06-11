<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\FbrDiLimit;
use App\Models\MultiTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Super-admin management of per-Company FBR-DI plan limits / overrides.
 */
class FbrDiLimitController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $limits = FbrDiLimit::query()->get()->keyBy('tenant_id');

        $rows = MultiTenant::query()->get()->map(function ($t) use ($limits) {
            $l = $limits->get($t->id);
            return [
                'tenant_id'             => $t->id,
                'company'               => $t->name ?? $t->id,
                'plan_type'             => $l?->plan_type ?? 'single',
                'monthly_invoice_limit' => $l?->monthly_invoice_limit,
                'businesses_limit'      => $l?->businesses_limit,
            ];
        });

        return $this->sendResponse($rows->values(), 'FBR-DI limits retrieved.');
    }

    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id'             => 'required|string',
            'plan_type'             => 'nullable|in:single,agent',
            'monthly_invoice_limit' => 'nullable|integer|min:0',
            'businesses_limit'      => 'nullable|integer|min:0',
        ]);

        $limit = FbrDiLimit::updateOrCreate(
            ['tenant_id' => $data['tenant_id']],
            [
                'plan_type'             => $data['plan_type'] ?? 'single',
                'monthly_invoice_limit' => $data['monthly_invoice_limit'] ?? null,
                'businesses_limit'      => $data['businesses_limit'] ?? null,
            ]
        );

        return $this->sendResponse($limit, 'FBR-DI limit saved.');
    }
}
