<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    /**
     * Seed the Platform Super Admin.
     *
     * Login : superadmin@noovapos-gls.com / 123456
     * Role  : platform_super_admin
     *
     * Uses firstOrCreate so re-running the seeder is safe.
     */
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@noovapos-gls.com'],
            [
                'first_name'        => 'Super',
                'last_name'         => 'Admin',
                'email_verified_at' => Carbon::now(),
                'password'          => Hash::make('123456'),
                'status'            => 1,
            ]
        );

        $superAdminRole = Role::whereName(Role::SUPER_ADMIN)->first();

        if ($superAdmin && $superAdminRole && ! $superAdmin->hasRole(Role::SUPER_ADMIN)) {
            $superAdmin->assignRole($superAdminRole);
        }
    }
}
