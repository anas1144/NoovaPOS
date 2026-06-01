<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Alias for DatabaseSeeder — both run the same central seed sequence.
 * Use:  php artisan db:seed --class=CentralDatabaseSeeder
 */
class CentralDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);
    }
}
