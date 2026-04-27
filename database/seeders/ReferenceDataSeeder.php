<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds only shared reference/lookup tables.
 * Safe to run on production without touching school or user data.
 *
 * Usage: php artisan db:seed --class=ReferenceDataSeeder
 */
class ReferenceDataSeeder extends Seeder
{
    public function run()
    {
        $this->call(BloodGroupsTableSeeder::class);
        $this->call(ClassTypesTableSeeder::class);
        $this->call(UserTypesTableSeeder::class);
        $this->call(NationalitiesTableSeeder::class);
        $this->call(StatesTableSeeder::class);
        $this->call(LgasTableSeeder::class);
    }
}
