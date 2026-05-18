<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateChangePasswordRequest;
use App\Http\Requests\UpdateChangeUserPasswordRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\POSRegister;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\UserStore;
use App\Repositories\UserRepository;
use App\Services\TenantSubscriptionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class UserAPIController
 */
class UserAPIController extends AppBaseController
{
    /** @var UserRepository */
    private $userRepository;

    public function __construct(
        UserRepository $userRepository,
        private readonly TenantSubscriptionService $tenantSubscriptionService
    )
    {
        $this->userRepository = $userRepository;
    }

    public function index(Request $request): UserCollection
    {
        $perPage = getPageSize($request);
        $users = $this->userRepository->getUsers($perPage);
        UserResource::usingWithCollection();

        return new UserCollection($users);
    }

    public function store(CreateUserRequest $request): UserResource
    {
        $input = $request->all();
        $this->tenantSubscriptionService->assertWithinLimit(currentTenantId(), 'users');
        $user = $this->userRepository->storeUser($input);

        return new UserResource($user);
    }

    public function show($id): UserResource
    {
        $user = $this->userRepository->withoutGlobalScope('tenant')->find($id);

        return new UserResource($user);
    }

    /**
     * @return UserResource|JsonResponse
     */
    public function update(UpdateUserRequest $request, $id)
    {
        $user = $this->userRepository->withoutGlobalScope('tenant')->find($id);

        if (Auth::id() == $user->id) {
            return $this->sendError('User can\'t be updated.');
        }
        $input = $request->all();
        $user = $this->userRepository->updateUser($input, $user->id);

        return new UserResource($user);
    }

    public function destroy($id): JsonResponse
    {
        $user = $this->userRepository->withoutGlobalScope('tenant')->find($id);

        if (Auth::id() == $user->id) {
            return $this->sendError(__('messages.error.user_cant_delete'));
        }
        $this->userRepository->delete($user->id);

        return $this->sendSuccess('User deleted successfully');
    }

    public function editProfile(): UserResource
    {
        $user = Auth::user();

        return new UserResource($user);
    }

    public function updateProfile(UpdateUserProfileRequest $request): UserResource
    {
        $input = $request->all();
        $updateUser = $this->userRepository->updateUserProfile($input);

        return new UserResource($updateUser);
    }

    public function changePassword(UpdateChangePasswordRequest $request): JsonResponse
    {
        $input = $request->all();
        try {
            $this->userRepository->updatePassword($input);

            return $this->sendSuccess('Password updated successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function changeUserPassword(UpdateChangeUserPasswordRequest $request): JsonResponse
    {
        $input = $request->all();
        try {
            $this->userRepository->updateUserPassword($input);

            return $this->sendSuccess('Password updated successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function updateLanguage(Request $request): JsonResponse
    {
        $language = $request->get('language');
        $user = Auth::user();
        $user->update([
            'language' => $language,
        ]);

        return $this->sendResponse($user->language, 'Language Updated Successfully');
    }

    public function config(Request $request)
    {
        $user = Auth::user();

        if ($user->hasRole(Role::ADMIN)) {
            $storeModal = false;
        } else {
            if (Store::where('tenant_id', $user->tenant_id)->where('status', 1)->exists()) {
                $storeModal = false;
            } else {
                $userStores = UserStore::where('user_id', $user->id)->get();
                if ($userStores->count() > 0) {
                    foreach ($userStores as $userStore) {
                        if ($userStore->store->status == 1) {
                            $storeModal = false;
                            $user->update([
                                'tenant_id' => $userStore->store->tenant_id
                            ]);
                            break;
                        } else {
                            $userStore->delete();
                            $storeModal = true;
                        }
                    }
                } else {
                    $storeModal = true;
                }
            }
        }

        $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();

        $composerFile = file_get_contents('../composer.json');
        $composerData = json_decode($composerFile, true);
        $currentVersion = isset($composerData['version']) ? $composerData['version'] : '';
        $dateFormat = getSettingValue('date_format');

        $openRegister = POSRegister::where('user_id', Auth::id())
            ->whereNull('closed_at')
            ->exists();

        return $this->sendResponse([
            'store_name' => getActiveStoreName(),
            'store_logo' => getLogoUrl(),
            'permissions' => $userPermissions,
            'version' => $currentVersion,
            'date_format' => $dateFormat,
            'store_modal' => $storeModal,
            'is_version' => getSettingValue('show_version_on_footer'),
            'is_currency_right' => getSettingValue('is_currency_right'),
            'open_register' => $openRegister ? false : true,
        ], 'Config retrieved successfully.');
    }
}
