<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleCollection;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleTemplate;
use App\Models\Shop;
use App\Models\User;
use App\Repositories\RoleRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleAPIController extends AppBaseController
{
    /**
     * @var RoleRepository
     */
    private $roleRepository;

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function index(Request $request): RoleCollection
    {
        $perPage = getPageSize($request);
        $roles = $perPage == 0 ? $this->roleRepository->all() : $this->roleRepository->paginate($perPage);
        // $roles = $this->roleRepository->paginate($perPage);
        RoleResource::usingWithCollection();

        return new RoleCollection($roles);
    }

    public function store(CreateRoleRequest $request): RoleResource
    {
        $input = $request->all();
        $role = $this->roleRepository->storeRole($input);

        return new RoleResource($role);
    }

    /**
     * Role templates this tenant may use, filtered to the shop types the tenant
     * actually operates. Permissions are super-admin-defined; the tenant just
     * picks a template.
     */
    public function availableTemplates(): JsonResponse
    {
        $shopTypes = Shop::query()
            ->whereNotNull('shop_type')
            ->distinct()
            ->pluck('shop_type')
            ->filter()
            ->values();

        if ($shopTypes->isEmpty()) {
            $shopTypes = collect(['retail']);
        }

        // RoleTemplate is pinned to the central connection, so this query is
        // safe regardless of the active tenant database.
        $templates = RoleTemplate::query()
            ->whereIn('shop_type', $shopTypes)
            ->orderBy('display_name')
            ->get(['id', 'name', 'display_name', 'shop_type', 'permissions'])
            ->map(fn ($t) => [
                'id'                => $t->id,
                'display_name'      => $t->display_name,
                'shop_type'         => $t->shop_type,
                'permissions_count' => is_array($t->permissions) ? count($t->permissions) : 0,
            ]);

        return $this->sendResponse($templates, 'Available role templates retrieved successfully.');
    }

    /**
     * Create a tenant role from a shop-type template. The tenant supplies only a
     * name; the permission set comes from the template (no raw permission editing).
     */
    public function storeFromTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'template_id' => 'required|integer',
            'name'        => 'required|string|max:120',
        ]);

        // Validate the template against the central connection explicitly
        // (avoids cross-database "exists" rule issues once the DB switch is on).
        $template = RoleTemplate::find($data['template_id']);
        if (! $template) {
            return $this->sendError('Selected role template was not found.', 422);
        }

        // Guard: the template's shop type must be one the tenant operates.
        $tenantShopTypes = Shop::query()->distinct()->pluck('shop_type')->filter()->all();
        if (! empty($tenantShopTypes) && ! in_array($template->shop_type, $tenantShopTypes, true)) {
            return $this->sendError('This template is not available for your shop types.', 422);
        }

        $name = Str::slug($data['name'], '_');
        if (Role::where('name', $name)->exists()) {
            return $this->sendError('A role with a similar name already exists.', 422);
        }

        $role = Role::create([
            'name'         => $name,
            'display_name' => $data['name'],
            'guard_name'   => 'web',
        ]);

        // Attach only permissions that actually exist in this database.
        $permissionNames = Permission::whereIn('name', $template->permissions ?? [])
            ->pluck('name')
            ->all();
        if (! empty($permissionNames)) {
            $role->givePermissionTo($permissionNames);
        }

        return $this->sendResponse(
            [
                'id'           => $role->id,
                'name'         => $role->name,
                'display_name' => $role->display_name,
                'permissions'  => $permissionNames,
            ],
            'Role created from template successfully.'
        );
    }

    public function show(Role $role): RoleResource
    {
        return new RoleResource($role);
    }

    /**
     * @return RoleResource|JsonResponse
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        if ($role->name == Role::ADMIN) {
            return $this->sendError('Admin role Can\'t be updated.');
        }

        $input = $request->all();
        $role = $this->roleRepository->updateRole($input, $role->id);

        return new RoleResource($role);
    }

    public function destroy($id): JsonResponse
    {
        /** @var Role $role */
        $role = Role::findOrFail($id);
        $usersCount = User::withoutGlobalScope('tenant')->role($role->name)->count();
        if ($role->users->count() > 0 || $usersCount > 0) {
            return $this->sendError(__('messages.error.role_cant_delete', ['role' => $role->display_name]));
        }
        $role->delete();

        return $this->sendSuccess('Role deleted successfully');
    }
}
