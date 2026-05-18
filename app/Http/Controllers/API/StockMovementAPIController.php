<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\Role;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockMovementAPIController extends AppBaseController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = getPageSize($request);
        $query = $this->baseQuery($request);

        $movements = $query->orderByDesc('stock_movements.id')->paginate($perPage);

        $data = $movements->through(function ($row) {
            return [
                'id' => $row->id,
                'tenant_id' => $row->tenant_id,
                'store_id' => $row->store_id,
                'shop_id' => $row->shop_id,
                'warehouse_id' => $row->warehouse_id,
                'warehouse_name' => $row->warehouse_name,
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'product_code' => $row->product_code,
                'movement_type' => $row->movement_type,
                'direction' => $row->direction,
                'quantity' => (float) $row->quantity,
                'reference_type' => $row->reference_type,
                'reference_id' => $row->reference_id,
                'created_by' => $row->created_by,
                'created_by_name' => trim(($row->created_by_first_name ?? '') . ' ' . ($row->created_by_last_name ?? '')),
                'created_at' => $row->created_at,
            ];
        });

        return $this->sendResponse($data, 'Stock movements retrieved successfully');
    }

    public function summary(Request $request): JsonResponse
    {
        $query = $this->baseQuery($request);

        $totals = (clone $query)->selectRaw("
            COALESCE(SUM(CASE WHEN stock_movements.direction = 'in' THEN stock_movements.quantity ELSE 0 END), 0) as total_in,
            COALESCE(SUM(CASE WHEN stock_movements.direction = 'out' THEN stock_movements.quantity ELSE 0 END), 0) as total_out,
            COALESCE(SUM(CASE WHEN stock_movements.direction = 'in' THEN stock_movements.quantity ELSE -stock_movements.quantity END), 0) as net_quantity
        ")->first();

        $byType = (clone $query)->selectRaw("
            stock_movements.movement_type,
            COALESCE(SUM(CASE WHEN stock_movements.direction = 'in' THEN stock_movements.quantity ELSE 0 END), 0) as qty_in,
            COALESCE(SUM(CASE WHEN stock_movements.direction = 'out' THEN stock_movements.quantity ELSE 0 END), 0) as qty_out
        ")->groupBy('stock_movements.movement_type')->get();

        return $this->sendResponse([
            'totals' => [
                'total_in' => (float) ($totals->total_in ?? 0),
                'total_out' => (float) ($totals->total_out ?? 0),
                'net_quantity' => (float) ($totals->net_quantity ?? 0),
            ],
            'by_type' => $byType->map(fn($row) => [
                'movement_type' => $row->movement_type,
                'in' => (float) $row->qty_in,
                'out' => (float) $row->qty_out,
            ])->values(),
        ], 'Stock movement summary retrieved successfully');
    }

    private function baseQuery(Request $request)
    {
        $query = StockMovement::query()
            ->leftJoin('warehouses', 'warehouses.id', '=', 'stock_movements.warehouse_id')
            ->leftJoin('products', 'products.id', '=', 'stock_movements.product_id')
            ->leftJoin('users as movement_users', 'movement_users.id', '=', 'stock_movements.created_by')
            ->select([
                'stock_movements.*',
                'warehouses.name as warehouse_name',
                'products.name as product_name',
                'products.code as product_code',
                'movement_users.first_name as created_by_first_name',
                'movement_users.last_name as created_by_last_name',
            ]);

        $user = Auth::user();
        $isPlatformSuperAdmin = $user && $user->hasRole(Role::SUPER_ADMIN);

        if (!$isPlatformSuperAdmin) {
            $query->where('stock_movements.tenant_id', currentTenantId());
        }

        if ($isPlatformSuperAdmin && $request->filled('tenant_id')) {
            $query->where('stock_movements.tenant_id', $request->get('tenant_id'));
        }

        foreach (['store_id', 'shop_id', 'warehouse_id', 'product_id', 'reference_id'] as $field) {
            if ($request->filled($field)) {
                $query->where("stock_movements.$field", $request->get($field));
            }
        }

        foreach (['movement_type', 'direction', 'reference_type'] as $field) {
            if ($request->filled($field)) {
                $query->where("stock_movements.$field", $request->get($field));
            }
        }

        if ($request->filled('start_date')) {
            $query->whereDate('stock_movements.created_at', '>=', $request->get('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('stock_movements.created_at', '<=', $request->get('end_date'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.code', 'like', "%{$search}%")
                    ->orWhere('warehouses.name', 'like', "%{$search}%")
                    ->orWhere('stock_movements.movement_type', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
