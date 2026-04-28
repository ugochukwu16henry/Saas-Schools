<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DormsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (DB::table('dorms')->count() > 0) {
            return;
        }
        $data = [
            ['name' => 'Faith Hostel'],
            ['name' => 'Peace Hostel'],
            ['name' => 'Grace Hostel'],
            ['name' => 'Success Hostel'],
            ['name' => 'Trust Hostel'],
        ];
        DB::table('dorms')->insert($data);
    }
}
