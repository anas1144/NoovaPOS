<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateShopRequest;
use App\Http\Requests\UpdateShopRequest;
use App\Http\Resources\ShopCollection;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use App\Models\UserShop;
use App\Repositories\ShopRepository;
use App\Services\TenantSubscriptionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ShopAPIController extends AppBaseController
{
    public function __construct(
        private readonly ShopRepository $shopRepository,
        private readonly TenantSubscriptionService $tenantSubscriptionService
    )
    {
    }

    public function index(Request $request): ShopCollection
    {
        $perPage = getPageSize($request);
        $query = $this->shopRepository;

        if ($storeId = $request->get('store_id')) {
            $query = $query->findWhere(['store_id' => $storeId]);
            ShopResource::usingWithCollection();
            return new ShopCollection($query);
        }

        $shops = $this->shopRepository->paginate($perPage);
        ShopResource::usingWithCollection();

        return new ShopCollection($shops);
    }

    public function store(CreateShopRequest $request): ShopResource
    {
        try {
            DB::beginTransaction();
            $this->tenantSubscriptionService->assertWithinLimit(currentTenantId(), 'shops');
            $shop = $this->shopRepository->create($request->validated());
            DB::commit();

            return new ShopResource($shop);
        } catch (Exception $exception) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($exception->getMessage());
        }
    }

    public function show(Shop $shop): ShopResource
    {
        return new ShopResource($shop);
    }

    public function update(UpdateShopRequest $request, Shop $shop): ShopResource
    {
        try {
            DB::beginTransaction();
            $shop = $this->shopRepository->update($request->validated(), $shop->id);
            DB::commit();

            return new ShopResource($shop);
        } catch (Exception $exception) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($exception->getMessage());
        }
    }

    public function destroy(Shop $shop): JsonResponse
    {
        try {
            DB::beginTransaction();
            UserShop::where('shop_id', $shop->id)->delete();
            $this->shopRepository->delete($shop->id);
            DB::commit();

            return $this->sendSuccess('Shop deleted successfully.');
        } catch (Exception $exception) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($exception->getMessage());
        }
    }

    public function changeStatus(Shop $shop): JsonResponse
    {
        try {
            $shop->update(['status' => !$shop->status]);
            return $this->sendSuccess('Shop status changed successfully.');
        } catch (Exception $exception) {
            return $this->sendError($exception->getMessage());
        }
    }

    public function assignUsers(Request $request, Shop $shop): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|exists:users,id',
        ]);

        try {
            DB::beginTransaction();
            UserShop::where('shop_id', $shop->id)->delete();
            foreach ($request->get('user_ids', []) as $userId) {
                UserShop::create([
                    'user_id' => $userId,
                    'shop_id' => $shop->id,
                ]);
            }
            DB::commit();

            return $this->sendSuccess('Shop users assigned successfully.');
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage());
        }
    }
}
