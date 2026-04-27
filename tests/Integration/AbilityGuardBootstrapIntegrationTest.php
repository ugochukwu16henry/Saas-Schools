<?php

namespace Tests\Integration;

use App\Models\PlatformAdmin;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AbilityGuardBootstrapIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('Integration profile requires the pdo_sqlite extension.');
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Force deterministic matrix for this integration suite regardless of cached config state.
        config([
            'permissions.abilities.school.integration.teacher' => ['teacher'],
            'permissions.abilities.school.integration.admin_only' => ['super_admin', 'admin'],
            'permissions.abilities.platform.integration.manage' => ['platform_admin'],
        ]);

        if (! Route::has('integration.ability.school.teacher')) {
            Route::middleware(['web', 'auth', 'ability:school.integration.teacher'])
                ->get('/_integration/ability/school-teacher', function () {
                    return response('ok', 200);
                })
                ->name('integration.ability.school.teacher');
        }

        if (! Route::has('integration.ability.school.admin_only')) {
            Route::middleware(['web', 'auth', 'ability:school.integration.admin_only'])
                ->get('/_integration/ability/school-admin-only', function () {
                    return response('ok', 200);
                })
                ->name('integration.ability.school.admin_only');
        }

        if (! Route::has('integration.ability.platform.manage')) {
            Route::middleware(['web', 'auth:platform', 'ability:platform.integration.manage'])
                ->get('/_integration/ability/platform-manage', function () {
                    return response('ok', 200);
                })
                ->name('integration.ability.platform.manage');
        }
    }

    public function testSchoolGuardTeacherCanAccessTeacherAbility()
    {
        $teacher = $this->createSchoolUser('teacher', 'integration.teacher@example.test', 'INT-TCH-001');

        $response = $this->actingAs($teacher, 'web')->get('/_integration/ability/school-teacher');

        $response->assertOk();
    }

    public function testSchoolGuardTeacherCannotAccessAdminOnlyAbility()
    {
        $teacher = $this->createSchoolUser('teacher', 'integration.teacher.denied@example.test', 'INT-TCH-002');

        $response = $this->actingAs($teacher, 'web')->get('/_integration/ability/school-admin-only');

        $response->assertForbidden();
    }

    public function testPlatformGuardAdminCanAccessPlatformAbility()
    {
        $platformAdmin = new PlatformAdmin([
            'name' => 'Integration Platform Admin',
            'email' => 'integration.platform.admin@example.test',
            'password' => Hash::make('secret12345'),
        ]);
        $platformAdmin->save();

        $response = $this->actingAs($platformAdmin, 'platform')->get('/_integration/ability/platform-manage');

        $response->assertOk();
    }

    public function testSchoolGuardUserCannotAccessPlatformAbility()
    {
        $schoolAdmin = $this->createSchoolUser('super_admin', 'integration.school.admin@example.test', 'INT-SCH-001');

        $response = $this->actingAs($schoolAdmin, 'web')->get('/_integration/ability/platform-manage');

        $response->assertForbidden();
    }

    private function createSchoolUser(string $userType, string $email, string $code): User
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
