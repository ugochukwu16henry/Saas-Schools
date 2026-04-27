<?php

namespace Tests\Feature;

use App\Models\School;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tenant_scope_hides_other_school_users(): void
    {
        $schoolA = $this->createSchool('Alpha School');
        $schoolB = $this->createSchool('Beta School');

        $userA = $this->createStudent($schoolA->id, 'alpha.student@example.test');
        $this->createStudent($schoolB->id, 'beta.student@example.test');

        app()->instance('currentSchool', $schoolA);

        $visibleStudentIds = User::where('user_type', 'student')->pluck('id')->all();

        $this->assertCount(1, $visibleStudentIds);
        $this->assertSame([$userA->id], $visibleStudentIds);
    }

    public function test_school_id_is_auto_assigned_when_tenant_is_bound(): void
    {
        $school = $this->createSchool('Gamma School');
        app()->instance('currentSchool', $school);

        $user = User::create([
            'name' => 'Scoped Student',
            'email' => 'scoped.student@example.test',
            'username' => 'scopedstudent',
            'password' => bcrypt('password123!'),
            'user_type' => 'student',
            'code' => strtoupper(Str::random(10)),
            'photo' => '/global_assets/images/user.png',
        ]);

        $this->assertSame($school->id, $user->school_id);
    }

    private function createSchool(string $name): School
    {
        $slugBase = Str::slug($name);

        return School::create([
            'name' => $name,
            'slug' => $slugBase . '-' . Str::lower(Str::random(6)),
            'email' => Str::slug($name) . '-' . Str::lower(Str::random(5)) . '@example.test',
            'status' => 'trial',
            'free_student_limit' => 50,
        ]);
    }

    private function createStudent(int $schoolId, string $email): User
    {
        return User::create([
            'name' => 'Student ' . Str::upper(Str::random(4)),
            'email' => $email,
            'username' => Str::slug(Str::before($email, '@')),
            'password' => bcrypt('password123!'),
            'user_type' => 'student',
            'code' => strtoupper(Str::random(10)),
            'photo' => '/global_assets/images/user.png',
            'school_id' => $schoolId,
        ]);
    }
}
