<?php

namespace Tests\Feature;

use App\Models\PlatformAdmin;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AbilityActorIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (!Route::has('test.ability.school.marks')) {
            Route::middleware(['web', 'auth', 'ability:school.marks.manage'])
                ->get('/_ability_integration/school-marks', function () {
                    return response('ok', 200);
                })->name('test.ability.school.marks');
        }

        if (!Route::has('test.ability.school.settings')) {
            Route::middleware(['web', 'auth', 'ability:school.settings.manage'])
                ->get('/_ability_integration/school-settings', function () {
                    return response('ok', 200);
                })->name('test.ability.school.settings');
        }

        if (!Route::has('test.ability.platform.schools')) {
            Route::middleware(['web', 'auth:platform', 'ability:platform.schools.manage'])
                ->get('/_ability_integration/platform-schools', function () {
                    return response('ok', 200);
                })->name('test.ability.platform.schools');
        }

        if (!Route::has('test.ability.platform.affiliates')) {
            Route::middleware(['web', 'ability:platform.affiliates.manage'])
                ->get('/_ability_integration/platform-affiliates', function () {
                    return response('ok', 200);
                })->name('test.ability.platform.affiliates');
        }
    }

    public function testTeacherAllowedForSchoolMarksAbility()
    {
        $teacher = $this->createUser('teacher', 'teacher.integration@example.test', 'TCH-INT-001');

        $response = $this->actingAs($teacher)->get('/_ability_integration/school-marks');

        $response->assertOk();
    }

    public function testTeacherDeniedForSchoolSettingsAbility()
    {
        $teacher = $this->createUser('teacher', 'teacher.denied@example.test', 'TCH-INT-002');

        $response = $this->actingAs($teacher)->get('/_ability_integration/school-settings');

        $response->assertForbidden();
    }

    public function testPlatformAdminAllowedForPlatformSchoolsAbility()
    {
        $platformAdmin = PlatformAdmin::query()->create([
            'name' => 'Integration Platform Admin',
            'email' => 'platform.integration@example.test',
            'password' => Hash::make('secret12345'),
        ]);

        $response = $this->actingAs($platformAdmin, 'platform')->get('/_ability_integration/platform-schools');

        $response->assertOk();
    }

    public function testSchoolSuperAdminDeniedForPlatformAffiliatesAbility()
    {
        $schoolAdmin = $this->createUser('super_admin', 'school.admin.integration@example.test', 'SCH-INT-001');

        $response = $this->actingAs($schoolAdmin)->get('/_ability_integration/platform-affiliates');

        $response->assertForbidden();
    }

    private function createUser(string $userType, string $email, string $code): User
    {
        return User::query()->create([
            'name' => 'Integration User ' . $userType,
            'email' => $email,
            'code' => $code,
            'username' => null,
            'user_type' => $userType,
            'password' => Hash::make('secret12345'),
            'school_id' => null,
        ]);
    }
}
