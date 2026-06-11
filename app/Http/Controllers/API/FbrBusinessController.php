<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\FbrBusiness;
use App\Services\FbrDiQuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manage FBR Digital Invoice "Businesses" (the seller entities under a Company).
 */
class FbrBusinessController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $rows = FbrBusiness::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->boolean('status')))
            ->orderBy('name')->get();

        return $this->sendResponse($rows, 'FBR businesses retrieved.');
    }

    public function show(FbrBusiness $fbrBusiness): JsonResponse
    {
        return $this->sendResponse($fbrBusiness, 'FBR business retrieved.');
    }

    public function store(Request $request, FbrDiQuotaService $quota): JsonResponse
    {
        if (! $quota->canCreateBusiness($request->user()?->tenant_id)) {
            return $this->sendError('Business limit reached for your plan. Contact the administrator to raise it.', 422);
        }
        return $this->persist(new FbrBusiness(), $request, 'FBR business created.');
    }

    public function update(Request $request, FbrBusiness $fbrBusiness): JsonResponse
    {
        return $this->persist($fbrBusiness, $request, 'FBR business updated.');
    }

    public function destroy(FbrBusiness $fbrBusiness): JsonResponse
    {
        if ($fbrBusiness->invoices()->exists()) {
            return $this->sendError('Cannot delete a business that has invoices.', 422);
        }
        $fbrBusiness->delete();
        return $this->sendSuccess('FBR business removed.');
    }

    private function persist(FbrBusiness $business, Request $request, string $message): JsonResponse
    {
        $data = $request->validate([
            'store_id'          => 'nullable|integer',
            'name'              => 'required|string|max:191',
            'ntn_cnic'          => 'nullable|string|max:20',
            'strn'              => 'nullable|string|max:30',
            'province'          => 'nullable|string|max:60',
            'address'           => 'nullable|string|max:255',
            'business_activity' => 'nullable|string|max:120',
            'pos_id'            => 'nullable|string|max:60',
            'environment'      => 'nullable|in:sandbox,production',
            'sandbox_token'     => 'nullable|string',
            'production_token'  => 'nullable|string',
            'status'            => 'nullable|boolean',
        ]);

        // Keep existing tokens when not provided (don't blank secrets on edit).
        foreach (['sandbox_token', 'production_token'] as $secret) {
            if (! $request->filled($secret)) {
                unset($data[$secret]);
            }
        }

        $business->fill($data)->save();

        return $this->sendResponse($business->fresh(), $message);
    }
}
