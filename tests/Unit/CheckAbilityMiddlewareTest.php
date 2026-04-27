<?php

namespace Tests\Unit;

use App\Http\Middleware\Custom\CheckAbility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CheckAbilityMiddlewareTest extends TestCase
{
    public function testAllowsConfiguredSchoolActor()
    {
        config(['permissions.abilities.school.marks.manage' => ['super_admin', 'teacher']]);

        $webGuard = \Mockery::mock();
        $webGuard->shouldReceive('check')->once()->andReturn(true);
        $webGuard->shouldReceive('user')->once()->andReturn((object) ['user_type' => 'teacher']);

        Auth::shouldReceive('guard')->with('web')->twice()->andReturn($webGuard);

        Auth::shouldReceive('id')->zeroOrMoreTimes()->andReturn(12);

        $middleware = new CheckAbility();
        $request = Request::create('/marks/update/1/1/1/1', 'PUT');

        $response = $middleware->handle($request, function () {
            return response('ok', 200);
        }, 'school.marks.manage');

        $this->assertSame(200, $response->status());
    }

    public function testDeniesActorOutsideAbilityMatrix()
    {
        config(['permissions.abilities.school.settings.manage' => ['super_admin', 'admin']]);

        $webGuard = \Mockery::mock();
        $webGuard->shouldReceive('check')->once()->andReturn(true);
        $webGuard->shouldReceive('user')->once()->andReturn((object) ['user_type' => 'teacher']);

        Auth::shouldReceive('guard')->with('web')->twice()->andReturn($webGuard);

        Auth::shouldReceive('id')->zeroOrMoreTimes()->andReturn(45);

        $middleware = new CheckAbility();
        $request = Request::create('/super_admin/settings', 'PUT');

        try {
            $middleware->handle($request, function () {
                return response('ok', 200);
            }, 'school.settings.manage');
            $this->fail('Expected ability middleware to deny unlisted actor type.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('You are not allowed to perform this action.', $e->getMessage());
        }
    }

    public function testRedirectsToLoginWhenNoAuthenticatedActor()
    {
        config(['permissions.abilities.school.users.manage' => ['super_admin', 'admin']]);

        $webGuard = \Mockery::mock();
        $webGuard->shouldReceive('check')->once()->andReturn(false);

        Auth::shouldReceive('guard')->with('web')->once()->andReturn($webGuard);
        Auth::shouldReceive('id')->zeroOrMoreTimes()->andReturn(null);

        $middleware = new CheckAbility();
        $request = Request::create('/users', 'POST');

        $response = $middleware->handle($request, function () {
            return response('ok', 200);
        }, 'school.users.manage');

        $this->assertTrue($response->isRedirect());
        $this->assertSame(route('login'), $response->getTargetUrl());
    }

    public function testAllowsPlatformActorForPlatformAbility()
    {
        config(['permissions.abilities.platform.schools.manage' => ['platform_admin']]);

        $platformGuard = \Mockery::mock();

        $platformGuard->shouldReceive('check')->once()->andReturn(true);

        Auth::shouldReceive('guard')->with('platform')->once()->andReturn($platformGuard);
        Auth::shouldReceive('id')->zeroOrMoreTimes()->andReturn(null);

        $middleware = new CheckAbility();
        $request = Request::create('/platform/schools/10/suspend', 'PATCH');

        $response = $middleware->handle($request, function () {
            return response('ok', 200);
        }, 'platform.schools.manage');

        $this->assertSame(200, $response->status());
    }

    public function testSchoolRoutePrefersWebActorEvenWhenPlatformSessionExists()
    {
        config(['permissions.abilities.school.settings.manage' => ['super_admin', 'admin']]);

        $webGuard = \Mockery::mock();
        $webGuard->shouldReceive('check')->once()->andReturn(true);
        $webGuard->shouldReceive('user')->once()->andReturn((object) ['user_type' => 'super_admin']);

        Auth::shouldReceive('guard')->with('web')->twice()->andReturn($webGuard);

        Auth::shouldReceive('id')->zeroOrMoreTimes()->andReturn(501);

        $middleware = new CheckAbility();
        $request = Request::create('/super_admin/settings', 'PUT');

        $response = $middleware->handle($request, function () {
            return response('ok', 200);
        }, 'school.settings.manage');

        $this->assertSame(200, $response->status());
    }

    public function testPlatformRouteUsesPlatformActorWhenGuardHintPresent()
    {
        config(['permissions.abilities.platform.schools.manage' => ['platform_admin']]);

        $platformGuard = \Mockery::mock();

        $platformGuard->shouldReceive('check')->once()->andReturn(true);

        Auth::shouldReceive('guard')->with('platform')->once()->andReturn($platformGuard);
        Auth::shouldReceive('id')->zeroOrMoreTimes()->andReturn(601);

        $middleware = new CheckAbility();
        $request = Request::create('/platform/schools/7/suspend', 'PATCH');

        $response = $middleware->handle($request, function () {
            return response('ok', 200);
        }, 'platform.schools.manage');

        $this->assertSame(200, $response->status());
    }
}
