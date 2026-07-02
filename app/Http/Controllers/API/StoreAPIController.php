<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Http\Resources\StoreCollection;
use App\Http\Resources\StoreResource;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\User;
use App\Models\UserStore;
use App\Repositories\StoreRepository;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Exception;
use Illuminate\Support\Facades\Auth;

class StoreAPIController extends AppBaseController
{
    /** @var StoreRepository */
    private $storeRepository;

    public function __construct(StoreRepository $storeRepository)
    {
        $this->storeRepository = $storeRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): StoreCollection
    {
        $perPage = getPageSize($request);
        $userStores = UserStore::where('user_id', auth()->id())->pluck('store_id')->toArray();
        if (!empty($userStores)) {
            $stores = $this->storeRepository->whereIn('id', $userStores)->paginate($perPage);
        } else {
            $stores = $this->storeRepository->paginate($perPage);
        }

        StoreResource::usingWithCollection();

        return new StoreCollection($stores);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateStoreRequest $request)
    {
        // Stores are not created manually. Each store is provisioned by
        // subscribing to a plan for its shop type (one plan → one store).
        // This keeps store count tied to active subscriptions/billing.
        throw new UnprocessableEntityHttpException(
            'Stores are created by subscribing to a plan. Go to Billing to add a plan for the business type you need.'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        return new StoreResource($store);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreRequest $request, Store $store)
    {
        $input = $request->all();

        $store = $this->storeRepository->updateStore($input, $store->id);

        return new StoreResource($store);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        try {
            $this->storeRepository->deleteStore($store->id);

            return $this->sendSuccess(__('messages.success.store_deleted'));
        } catch (Exception $exception) {
            return $this->sendError($exception->getMessage());
        }
    }

    public function changeStore(Store $store)
    {
        try {
            // Record the user's selected store. tenant_id stays the same (all the
            // tenant's stores share it) — the active pointer is active_store_id.
            User::where('id', auth()->id())->update([
                'tenant_id'       => $store->tenant_id,
                'active_store_id' => $store->id,
            ]);

            return $this->sendSuccess(__('messages.success.active_store_changed'));
        } catch (Exception $exception) {
            return $this->sendError($exception->getMessage());
        }
    }

    public function changeStatus(Store $store)
    {
        try {
            if ($store->tenant_id == Auth::user()->tenant_id) {
                return $this->sendError(__('messages.error.cannot_deactivate_active_store'));
            }

            if ($store->status) {
                $userStore = UserStore::where('store_id', $store->id)->exists();
                if ($userStore) {
                    return $this->sendError(__('messages.error.cannot_disable_already_assigned_store'));
                }
            }

            $store->status = !$store->status;
            $store->save();

            return $this->sendSuccess(__('messages.success.store_status_changed'));
        } catch (Exception $exception) {
            return $this->sendError($exception->getMessage());
        }
    }

    public function changeDefaultStore(Store $store)
    {
        try {
            Store::where('id', '!=', $store->id)->where('is_default', true)->update(['is_default' => false]);
            $store->is_default = true;
            $store->status = true;
            $store->save();
            return $this->sendSuccess(__('Default store changed successfully'));
        } catch (Exception $exception) {
            return $this->sendError($exception->getMessage());
        }
    }
}
