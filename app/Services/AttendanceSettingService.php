<?php

namespace App\Services;

use App\Models\AttendanceSetting;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves attendance configuration for a tenant (optionally a store), layering
 * stored values over sane defaults. Settings live as a single JSON blob per
 * tenant/store so toggles can be added without migrations.
 */
class AttendanceSettingService
{
    /**
     * Full default configuration — mirrors Configuration → Attendance.
     */
    public function defaults(): array
    {
        return [
            // General
            'enable_module'        => true,
            'enable_self_checkin'  => true,
            'enable_self_checkout' => true,
            'enable_task_tracking' => true,
            'enable_break_tracking' => true,
            'enable_dashboard'     => true,

            // Attendance methods
            'method_face'        => true,
            'method_fingerprint' => false, // future-ready, off by default
            'method_barcode'     => true,
            'method_qr'          => true,
            'method_manual'      => true,

            // Biometric matching mode (camera = built-in; device = external
            // scanner/terminal via a no-code Device Connector).
            //   face_mode        : 'camera' | 'device'
            //   fingerprint_mode : 'camera_visual' (photo record only) | 'device' | 'webauthn'
            'face_mode'                => 'camera',
            'fingerprint_mode'         => 'camera_visual',
            'enable_device_connectors' => false,

            // Scanner
            'scanner_pos_camera'  => true,
            'auto_submit_on_scan' => true,
            'sound_success'       => true,
            'sound_error'         => true,
            'camera_mobile'       => true,
            'camera_desktop'      => true,

            // Security
            'prevent_multiple_checkins' => true,
            'require_active_shift'      => false,
            'require_location'          => false,
            'self_only'                 => true,
            'allow_admin_manual'        => true,
        ];
    }

    /**
     * Effective settings (defaults ∪ stored) for the given tenant/store.
     */
    public function all(?string $tenantId = null, ?int $storeId = null): array
    {
        $tenantId = $tenantId ?: (Auth::check() ? Auth::user()->tenant_id : null);

        $query = AttendanceSetting::query()->where('tenant_id', $tenantId);
        // Prefer a store-specific row, else the tenant-wide row (store_id null).
        $row = (clone $query)->where('store_id', $storeId)->first()
            ?? (clone $query)->whereNull('store_id')->first();

        $stored = is_array($row?->settings) ? $row->settings : [];

        return array_merge($this->defaults(), $stored);
    }

    public function enabled(string $key, ?string $tenantId = null, ?int $storeId = null): bool
    {
        return (bool) ($this->all($tenantId, $storeId)[$key] ?? false);
    }

    /**
     * Persist a (partial) settings change for the tenant/store.
     */
    public function update(array $values, ?string $tenantId = null, ?int $storeId = null): array
    {
        $tenantId = $tenantId ?: (Auth::check() ? Auth::user()->tenant_id : null);

        $row = AttendanceSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->first();

        // Only keep known keys, coerced to each default's type (bool or string).
        $clean = [];
        foreach ($this->defaults() as $key => $default) {
            if (array_key_exists($key, $values)) {
                $clean[$key] = is_bool($default) ? (bool) $values[$key] : (string) $values[$key];
            }
        }

        $merged = array_merge(
            is_array($row?->settings) ? $row->settings : [],
            $clean
        );

        AttendanceSetting::updateOrCreate(
            ['tenant_id' => $tenantId, 'store_id' => $storeId],
            ['settings' => $merged]
        );

        return array_merge($this->defaults(), $merged);
    }
}
