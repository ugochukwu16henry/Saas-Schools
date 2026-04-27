<?php

namespace Tests\Unit;

use App\Http\Middleware\Custom\CheckAbility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AbilityActorMatrixTest extends TestCase
{
    public function testTeacherAllowedForMarksAbility()
    {
        config(['permissions.abilities.school.marks.manage' => ['super_admin', 'admin', 'teacher']]);
        $this->mockSchoolActor('teacher', 301);

        $middleware = new CheckAbility();
        $request = Request::create('/marks/update/1/2/3/4', 'PUT');

        $response = $middleware->handle($request, function () {
            return response('ok', 200);
        }, 'school.marks.manage');

        $this->assertSame(200, $response->status());
    }

    public function testTeacherDeniedForSettingsAbility()
    {
        config(['permissions.abilities.school.settings.manage' => ['super_admin', 'admin']]);
        $this->mockSchoolActor('teacher', 302);

        $middleware = new CheckAbility();
        $request = Request::create('/super_admin/settings', 'PUT');

        try {
            $middleware->handle($request, function () {
                return response('ok', 200);
            }, 'school.settings.manage');
            $this->fail('Expected school actor to be denied for school.settings.manage.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function testPlatformAdminAllowedForWebhookAbility()
    {
        config(['permissions.abilities.platform.webhooks.manage' => ['platform_admin']]);

        $platformGuard = \Mockery::mock();
        $platformGuard->shouldReceive('check')->once()->andReturn(true);

        Auth::shouldReceive('guard')->with('platform')->once()->andReturn($platformGuard);
        Auth::shouldReceive('id')->zeroOrMoreTimes()->andReturn(401);

        $middleware = new CheckAbility();
        $request = Request::create('/platform/webhooks', 'POST');

        $response = $middleware->handle($request, function () {
            return response('ok', 200);
        }, 'platform.webhooks.manage');

        $this->assertSame(200, $response->status());
    }

    public function testSchoolSuperAdminDeniedForPlatformAffiliateAbility()
    {
        config(['permissions.abilities.platform.affiliates.manage' => ['platform_admin']]);

        $platformGuard = \Mockery::mock();
        $platformGuard->shouldReceive('check')->once()->andReturn(false);

        Auth::shouldReceive('guard')->with('platform')->once()->andReturn($platformGuard);
        Auth::shouldReceive('id')->zeroOrMoreTimes()->andReturn(303);

        $middleware = new CheckAbility();
        $request = Request::create('/platform/affiliates/7/approve', 'PATCH');

        $response = $middleware->handle($request, function () {
            return response('ok', 200);
        }, 'platform.affiliates.manage');

        $this->assertTrue($response->isRedirect());
        $this->assertSame(route('login'), $response->getTargetUrl());
    }

    private function mockSchoolActor(string $userType, int $userId): void
    {
        $webGuard = \Mockery::mock();
        $webGuard->shouldReceive('check')->once()->andReturn(true);
        $webGuard->shouldReceive('user')->once()->andReturn((object) ['user_type' => $userType]);

        Auth::shouldReceive('guard')->with('web')->twice()->andReturn($webGuard);
        Auth::shouldReceive('id')->zeroOrMoreTimes()->andReturn($userId);
    }
}
