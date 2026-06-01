<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateFbrProfileRequest;
use App\Http\Requests\UpdateFbrProfileRequest;
use App\Models\FbrInvoice;
use App\Models\FbrProfile;
use App\Models\Sale;
use App\Services\FbrSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class FbrProfileAPIController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = FbrProfile::query()->with(['store:id,name']);
        $storeId = $request->get('store_id');
        if (! empty($storeId)) {
            $query->where('store_id', $storeId);
        }

        $profiles = $query->orderByDesc('id')->get();

        return $this->sendResponse($profiles, 'FBR profiles retrieved successfully.');
    }

    public function show(FbrProfile $fbrProfile): JsonResponse
    {
        $fbrProfile->loadMissing(['store:id,name']);
        return $this->sendResponse($fbrProfile, 'FBR profile retrieved successfully.');
    }

    public function store(CreateFbrProfileRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $input = $request->validated();
            $tenantId = Auth::user()?->tenant_id;
            $storeId = $input['store_id'] ?? null;

            $exists = FbrProfile::query()
                ->where('tenant_id', $tenantId)
                ->where('store_id', $storeId)
                ->exists();
            if ($exists) {
                throw new UnprocessableEntityHttpException('FBR profile already exists for this store.');
            }

            $profile = FbrProfile::create($input);

            DB::commit();
            return $this->sendResponse($profile, 'FBR profile created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function update(UpdateFbrProfileRequest $request, FbrProfile $fbrProfile): JsonResponse
    {
        try {
            DB::beginTransaction();

            $input = $request->validated();
            $targetStoreId = array_key_exists('store_id', $input) ? $input['store_id'] : $fbrProfile->store_id;
            $tenantId = Auth::user()?->tenant_id;

            $exists = FbrProfile::query()
                ->where('tenant_id', $tenantId)
                ->where('store_id', $targetStoreId)
                ->where('id', '!=', $fbrProfile->id)
                ->exists();
            if ($exists) {
                throw new UnprocessableEntityHttpException('FBR profile already exists for this store.');
            }

            $fbrProfile->update($input);

            DB::commit();
            return $this->sendResponse($fbrProfile->fresh(), 'FBR profile updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function destroy(FbrProfile $fbrProfile): JsonResponse
    {
        $fbrProfile->delete();
        return $this->sendSuccess('FBR profile deleted successfully.');
    }

    // ---- FBR invoice queue ----
    public function invoices(Request $request): JsonResponse
    {
        $query = FbrInvoice::query()->with('profile:id,business_name,mode');
        foreach (['status', 'mode', 'fbr_profile_id'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->get($f));
            }
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->get('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->get('end_date'));
        }
        return $this->sendResponse(
            $query->orderByDesc('id')->paginate(getPageSize($request)),
            'FBR invoices retrieved successfully.'
        );
    }

    public function queueFromSale(Sale $sale, FbrSubmissionService $service): JsonResponse
    {
        $invoice = $service->queueForSale($sale);
        if (!$invoice) {
            return $this->sendError('No enabled FBR profile for this tenant.');
        }
        return $this->sendResponse($invoice, 'FBR invoice queued for sale.');
    }

    public function submitInvoice(FbrInvoice $fbrInvoice, FbrSubmissionService $service): JsonResponse
    {
        $invoice = $service->submit($fbrInvoice);
        return $this->sendResponse($invoice, 'FBR invoice submitted.');
    }

    public function retryInvoice(FbrInvoice $fbrInvoice, FbrSubmissionService $service): JsonResponse
    {
        $invoice = $service->retry($fbrInvoice);
        return $this->sendResponse($invoice, 'FBR invoice retry requested.');
    }

    public function processFbrQueue(Request $request, FbrSubmissionService $service): JsonResponse
    {
        $limit = max(1, min(500, (int) $request->get('limit', 50)));
        return $this->sendResponse(
            $service->processQueue($limit),
            'FBR queue processed.'
        );
    }
}

