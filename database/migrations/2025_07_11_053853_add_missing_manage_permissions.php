<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

/**
 * Creates any manage_* permissions that are referenced by
 * GenerateCrudPermissionsSeeder (which runs in the very next migration)
 * but were never inserted by a prior migration.
 *
 * Safe to run on both fresh installs and existing databases — every
 * upsert is guarded by a "does not exist" check.
 */
return new class extends Migration
{
    /** guard_name used by the application */
    private string $guard;

    public function up(): void
    {
        $this->guard = config('auth.defaults.guard', 'web');

        $missing = [
            'manage_shops'          => 'Manage Shops',
            'manage_email_templates'=> 'Manage Email Templates',
            'manage_reports'        => 'Manage Reports',
            'manage_quotations'     => 'Manage Quotations',
            'manage_sms_templates'  => 'Manage SMS Templates',
            'manage_sms_apis'       => 'Manage SMS APIs',
            'manage_variations'     => 'Manage Variations',
            'manage_language'       => 'Manage Language',
        ];

        foreach ($missing as $name => $displayName) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => $this->guard],
                ['display_name' => $displayName]
            );
        }
    }

    public function down(): void
    {
        // Intentionally left empty — removing permissions can break role
        // assignments on rollback in unpredictable ways.
    }
};
