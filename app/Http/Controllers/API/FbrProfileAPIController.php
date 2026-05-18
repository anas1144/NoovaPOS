<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateFbrProfileRequest;
use App\Http\Requests\UpdateFbrProfileRequest;
use App\Models\FbrProfile;
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
}

