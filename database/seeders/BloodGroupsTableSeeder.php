<?php

namespace Database\Seeders;

use App\Models\BloodGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class BloodGroupsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $bgs = ['O-', 'O+', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];

        if (DB::table('blood_groups')->count() === 0) {
            DB::table('blood_groups')->insert(
                array_map(fn($bg) => ['name' => $bg], $bgs)
            );
        }
    }
}
