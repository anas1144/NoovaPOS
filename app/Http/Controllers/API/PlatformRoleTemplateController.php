<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\RoleTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Super-admin management of role+permission templates (per shop type).
 *
 * Permissions are super-admin-only: this controller is the single place where
 * roles and their permission sets are defined. Tenant owners later instantiate
 * tenant roles from these templates without touching raw permissions.
 */
class PlatformRoleTemplateController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = RoleTemplate::query()->orderBy('shop_type')->orderBy('display_name');

        if ($shopType = $request->get('shop_type')) {
            $query->where('shop_type', $shopType);
        }

        return $this->sendResponse($query->get(), 'Role templates retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);

        $template = RoleTemplate::create([
            'name'         => $data['name'],
            'display_name' => $data['display_name'],
            'shop_type'    => $data['shop_type'],
            'permissions'  => $data['permissions'],
            'is_system'    => false,
        ]);

        return $this->sendResponse($template, 'Role template created successfully.');
    }

    public function update(Request $request, RoleTemplate $roleTemplate): JsonResponse
    {
        $data = $this->validatePayload($request, $roleTemplate);

        $roleTemplate->update([
            'display_name' => $data['display_name'],
            'shop_type'    => $data['shop_type'],
            'permissions'  => $data['permissions'],
        ]);

        return $this->sendResponse($roleTemplate, 'Role template updated successfully.');
    }

    public function destroy(RoleTemplate $roleTemplate): JsonResponse
    {
        if ($roleTemplate->is_system) {
            return $this->sendError('System templates cannot be deleted.');
        }

        $roleTemplate->delete();

        return $this->sendSuccess('Role template deleted successfully.');
    }

    /**
     * Full permission catalog grouped by module (manage_* parent + edit/create/
     * view/delete children), plus the list of shop types. The super admin picks
     * from this when building a template.
     */
    public function permissionCatalog(): JsonResponse
    {
        $permissions = Permission::query()->orderBy('name')->get();

        $managePermissions = $permissions->filter(
            fn ($p) => Str::startsWith($p->name, 'manage_')
        );

        $byName = $permissions->keyBy('name');

        $modules = $managePermissions->map(function ($permission) use ($byName) {
            $module = Str::after($permission->name, 'manage_');

            $children = collect(['view', 'create', 'edit', 'delete'])
                ->map(function ($action) use ($module, $byName) {
                    $name = "{$action}_{$module}";
                    $perm = $byName->get($name);

                    return $perm ? ['id' => $perm->id, 'name' => $name] : null;
                })
                ->filter()
                ->values();

            return [
                'id'           => $permission->id,
                'name'         => $permission->name,
                'display_name' => $permission->display_name ?? Str::headline($module),
                'module'       => $module,
                'children'     => $children,
            ];
        })->values();

        return $this->sendResponse([
            'modules'    => $modules,
            'shop_types' => collect(Plan::SHOP_TYPES)->map(
                fn ($label, $value) => ['value' => $value, 'label' => $label]
            )->values(),
        ], 'Permission catalog retrieved successfully.');
    }

    private function validatePayload(Request $request, ?RoleTemplate $existing = null): array
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:120',
            'display_name'  => 'required|string|max:120',
            'shop_type'     => 'required|string|max:60',
            'permissions'   => 'required|array|min:1',
            'permissions.*' => 'string',
        ]);

        // Normalise the machine name to a slug, suffixed by shop type for uniqueness.
        $validated['name'] = Str::slug($validated['name'], '_');

        // Keep only permission names that actually exist.
        $validNames = Permission::whereIn('name', $validated['permissions'])
            ->pluck('name')
            ->all();
        $validated['permissions'] = array_values(array_intersect($validated['permissions'], $validNames));

        if (empty($validated['permissions'])) {
            abort(422, 'None of the supplied permissions are valid.');
        }

        return $validated;
    }
}
