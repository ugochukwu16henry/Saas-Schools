<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentRecordsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (!Section::query()->exists() || StudentRecord::query()->exists()) {
            return;
        }

        $this->createStudentRecord();
        $this->createManyStudentRecords(3);
    }

    protected function createManyStudentRecords(int $count)
    {
        $sections = Section::all();

        foreach ($sections as $section) {
            User::factory()
                ->has(
                    StudentRecord::factory()
                        ->state([
                            'section_id' => $section->id,
                            'my_class_id' => $section->my_class_id,
                            'user_id' => function (User $user) {
                                return ['user_id' => $user->id];
                            },
                        ]),
                    'student_record'
                )
                ->count($count)
                ->create([
                    'user_type' => 'student',
                    'password' => Hash::make('student'),
                ]);
        }
    }

    protected function createStudentRecord()
    {
        $section = Section::first();

        if (!$section) {
            return;
        }

        $user = User::query()->firstOrCreate([
            'email' => 'student@student.com',
        ], [
            'name' => 'Demo Student',
            'user_type' => 'student',
            'username' => 'student',
            'password' => Hash::make('student'),
            'email' => 'student@student.com',
            'code' => strtoupper(Str::random(10)),
            'remember_token' => Str::random(10),
        ]);

        StudentRecord::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'session' => \App\Helpers\Qs::getCurrentSession(),
                'my_class_id' => $section->my_class_id,
                'section_id' => $section->id,
            ]
        );
    }
}
