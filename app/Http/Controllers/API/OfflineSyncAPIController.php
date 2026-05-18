<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Models\OfflineDevice;
use App\Models\Role;
use App\Models\SyncBatch;
use App\Models\SyncQueue;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OfflineSyncAPIController extends AppBaseController
{
    public function devices(Request $request): JsonResponse
    {
        $perPage = getPageSize($request);
        $query = $this->deviceQuery($request);

        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('device_uuid', 'like', "%{$search}%")
                    ->orWhere('platform', 'like', "%{$search}%");
            });
        }

        $devices = $query->with(['store:id,name', 'shop:id,name'])
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->sendResponse($devices, 'Offline devices retrieved successfully.');
    }

    public function registerDevice(Request $request): JsonResponse
    {
        $input = $request->validate([
            'device_uuid' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'store_id' => 'nullable|exists:stores,id',
            'shop_id' => 'nullable|exists:shops,id',
            'platform' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:60',
        ]);

        $tenantId = currentTenantId();

        if (!empty($input['store_id'])) {
            $storeExists = DB::table('stores')
                ->where('tenant_id', $tenantId)
                ->where('id', $input['store_id'])
                ->exists();
            if (!$storeExists) {
                return $this->sendError('Selected store does not belong to the active tenant.');
            }
        }

        if (!empty($input['shop_id'])) {
            $shopExists = DB::table('shops')
                ->where('tenant_id', $tenantId)
                ->where('id', $input['shop_id'])
                ->exists();
            if (!$shopExists) {
                return $this->sendError('Selected shop does not belong to the active tenant.');
            }
        }

        $device = OfflineDevice::withoutGlobalScope('tenant')->updateOrCreate([
            'tenant_id' => $tenantId,
            'device_uuid' => $input['device_uuid'],
        ], [
            'name' => $input['name'],
            'store_id' => $input['store_id'] ?? null,
            'shop_id' => $input['shop_id'] ?? null,
            'platform' => $input['platform'] ?? null,
            'app_version' => $input['app_version'] ?? null,
            'status' => true,
            'last_seen_at' => now(),
        ]);

        return $this->sendResponse($device, 'Offline device registered successfully.');
    }

    public function heartbeat(int $offlineDevice): JsonResponse
    {
        $offlineDevice = OfflineDevice::withoutGlobalScope('tenant')->findOrFail($offlineDevice);
        $this->authorizeTenantDevice($offlineDevice);

        $offlineDevice->update(['last_seen_at' => now()]);

        return $this->sendSuccess('Offline device heartbeat recorded successfully.');
    }

    public function pushBatch(Request $request): JsonResponse
    {
        $input = $request->validate([
            'device_uuid' => 'required|string|max:100',
            'batch_uuid' => 'required|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.local_uuid' => 'required|string|max:100',
            'items.*.entity_type' => ['required', 'string', 'max:60', Rule::in(['sale', 'payment', 'customer', 'stock_adjustment'])],
            'items.*.operation' => ['nullable', 'string', 'max:30', Rule::in(['create', 'update', 'delete'])],
            'items.*.payload' => 'required|array',
        ]);

        $tenantId = currentTenantId();
        $device = OfflineDevice::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('device_uuid', $input['device_uuid'])
            ->firstOrFail();

        if (!$device->status) {
            return $this->sendError('Offline device is inactive.');
        }

        try {
            $result = DB::transaction(function () use ($input, $tenantId, $device) {
                $batch = SyncBatch::withoutGlobalScope('tenant')->updateOrCreate([
                    'tenant_id' => $tenantId,
                    'batch_uuid' => $input['batch_uuid'],
                ], [
                    'offline_device_id' => $device->id,
                    'status' => SyncBatch::STATUS_QUEUED,
                    'items_count' => count($input['items']),
                ]);

                $queued = [];
                foreach ($input['items'] as $item) {
                    $queueItem = SyncQueue::withoutGlobalScope('tenant')
                        ->where('tenant_id', $tenantId)
                        ->where('offline_device_id', $device->id)
                        ->where('local_uuid', $item['local_uuid'])
                        ->first();

                    if (!$queueItem) {
                        $queueItem = SyncQueue::withoutGlobalScope('tenant')->create([
                            'tenant_id' => $tenantId,
                            'offline_device_id' => $device->id,
                            'sync_batch_id' => $batch->id,
                            'local_uuid' => $item['local_uuid'],
                            'entity_type' => $item['entity_type'],
                            'operation' => $item['operation'] ?? 'create',
                            'payload' => $item['payload'],
                            'status' => SyncQueue::STATUS_QUEUED,
                            'error_message' => null,
                        ]);
                    } elseif (!in_array($queueItem->status, [SyncQueue::STATUS_SYNCED, SyncQueue::STATUS_PROCESSING], true)) {
                        $queueItem->update([
                            'sync_batch_id' => $batch->id,
                            'entity_type' => $item['entity_type'],
                            'operation' => $item['operation'] ?? 'create',
                            'payload' => $item['payload'],
                            'status' => SyncQueue::STATUS_QUEUED,
                            'error_message' => null,
                        ]);
                    }

                    $queued[] = [
                        'id' => $queueItem->id,
                        'local_uuid' => $queueItem->local_uuid,
                        'status' => $queueItem->status,
                    ];
                }

                $device->update(['last_seen_at' => now()]);

                return [
                    'batch' => $batch,
                    'items' => $queued,
                ];
            });

            return $this->sendResponse($result, 'Offline sync batch queued successfully.');
        } catch (Exception $exception) {
            return $this->sendError($exception->getMessage());
        }
    }

    public function queue(Request $request): JsonResponse
    {
        $perPage = getPageSize($request);
        $query = $this->queueQuery($request);

        foreach (['status', 'entity_type', 'operation', 'offline_device_id', 'sync_batch_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->get($field));
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('local_uuid', 'like', "%{$search}%")
                    ->orWhere('entity_type', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%");
            });
        }

        $items = $query->with(['device:id,name,device_uuid', 'batch:id,batch_uuid,status'])
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->sendResponse($items, 'Sync queue retrieved successfully.');
    }

    public function updateQueueStatus(Request $request, int $syncQueue): JsonResponse
    {
        $syncQueue = SyncQueue::withoutGlobalScope('tenant')->findOrFail($syncQueue);
        $this->authorizeTenantQueueItem($syncQueue);

        $input = $request->validate([
            'status' => ['required', Rule::in([
                SyncQueue::STATUS_QUEUED,
                SyncQueue::STATUS_PROCESSING,
                SyncQueue::STATUS_SYNCED,
                SyncQueue::STATUS_FAILED,
            ])],
            'error_message' => 'nullable|string',
            'server_reference_type' => 'nullable|string|max:255',
            'server_reference_id' => 'nullable|integer',
        ]);

        $syncQueue->update([
            'status' => $input['status'],
            'error_message' => $input['error_message'] ?? null,
            'server_reference_type' => $input['server_reference_type'] ?? $syncQueue->server_reference_type,
            'server_reference_id' => $input['server_reference_id'] ?? $syncQueue->server_reference_id,
            'attempted_at' => now(),
            'synced_at' => $input['status'] === SyncQueue::STATUS_SYNCED ? now() : null,
        ]);

        $this->refreshBatchStatus($syncQueue->sync_batch_id);

        return $this->sendResponse($syncQueue->refresh(), 'Sync queue status updated successfully.');
    }

    private function deviceQuery(Request $request)
    {
        $query = OfflineDevice::query();
        $this->scopeTenant($query, $request);

        return $query;
    }

    private function queueQuery(Request $request)
    {
        $query = SyncQueue::query();
        $this->scopeTenant($query, $request);

        return $query;
    }

    private function scopeTenant($query, Request $request): void
    {
        $user = Auth::user();
        $isPlatformSuperAdmin = $user && $user->hasRole(Role::SUPER_ADMIN);

        if ($isPlatformSuperAdmin) {
            $query->withoutGlobalScope('tenant');
            if ($request->filled('tenant_id')) {
                $query->where('tenant_id', $request->get('tenant_id'));
            }
            return;
        }

        if (!$isPlatformSuperAdmin) {
            $query->where('tenant_id', currentTenantId());
        }
    }

    private function authorizeTenantDevice(OfflineDevice $device): void
    {
        $user = Auth::user();
        if ($user && $user->hasRole(Role::SUPER_ADMIN)) {
            return;
        }

        abort_if($device->tenant_id !== currentTenantId(), 404);
    }

    private function authorizeTenantQueueItem(SyncQueue $syncQueue): void
    {
        $user = Auth::user();
        if ($user && $user->hasRole(Role::SUPER_ADMIN)) {
            return;
        }

        abort_if($syncQueue->tenant_id !== currentTenantId(), 404);
    }

    private function refreshBatchStatus(?int $batchId): void
    {
        if (!$batchId) {
            return;
        }

        $batch = SyncBatch::withoutGlobalScope('tenant')->find($batchId);
        if (!$batch) {
            return;
        }

        $syncedCount = SyncQueue::withoutGlobalScope('tenant')
            ->where('sync_batch_id', $batchId)
            ->where('status', SyncQueue::STATUS_SYNCED)
            ->count();
        $failedCount = SyncQueue::withoutGlobalScope('tenant')
            ->where('sync_batch_id', $batchId)
            ->where('status', SyncQueue::STATUS_FAILED)
            ->count();
        $itemsCount = SyncQueue::withoutGlobalScope('tenant')
            ->where('sync_batch_id', $batchId)
            ->count();

        $status = SyncBatch::STATUS_QUEUED;
        if ($itemsCount > 0 && $syncedCount === $itemsCount) {
            $status = SyncBatch::STATUS_SYNCED;
        } elseif ($failedCount > 0 && ($failedCount + $syncedCount) === $itemsCount) {
            $status = $syncedCount > 0 ? SyncBatch::STATUS_PARTIAL : SyncBatch::STATUS_FAILED;
        } elseif ($syncedCount > 0 || $failedCount > 0) {
            $status = SyncBatch::STATUS_PROCESSING;
        }

        $batch->update([
            'status' => $status,
            'items_count' => $itemsCount,
            'synced_count' => $syncedCount,
            'failed_count' => $failedCount,
            'completed_at' => in_array($status, [SyncBatch::STATUS_SYNCED, SyncBatch::STATUS_FAILED, SyncBatch::STATUS_PARTIAL], true) ? now() : null,
        ]);
    }
}
