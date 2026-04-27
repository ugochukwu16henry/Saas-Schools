<?php

namespace Tests\Feature;

use Tests\TestCase;

class AbilityRouteMiddlewareTest extends TestCase
{
    public function testSupportTeamRoutesRetainAbilityMiddleware()
    {
        $this->assertRouteHasMiddleware('students.bulk.store', 'ability:school.students.bulk_import');
        $this->assertRouteHasMiddleware('subjects.store', 'ability:school.subjects.manage');
        $this->assertRouteHasMiddleware('marks.update', 'ability:school.marks.manage');
        $this->assertRouteHasMiddleware('pins.store', 'ability:school.pins.manage');
        $this->assertRouteHasMiddleware('students.promote', 'ability:school.promotions.manage');
        $this->assertRouteHasMiddleware('classes.destroy', 'ability:school.classes.manage');
        $this->assertRouteHasMiddleware('sections.destroy', 'ability:school.sections.manage');
        $this->assertRouteHasMiddleware('grades.destroy', 'ability:school.grades.manage');
        $this->assertRouteHasMiddleware('dorms.destroy', 'ability:school.dorms.manage');
    }

    public function testPlatformMutationRoutesRetainAbilityMiddleware()
    {
        $this->assertRouteHasMiddleware('platform.billing_plans.store', 'ability:platform.billing_plans.manage');
        $this->assertRouteHasMiddleware('platform.schools.update_plan', 'ability:platform.schools.manage');
        $this->assertRouteHasMiddleware('platform.webhooks.store', 'ability:platform.webhooks.manage');
        $this->assertRouteHasMiddleware('platform.webhooks.destroy', 'ability:platform.webhooks.manage');
        $this->assertRouteHasMiddleware('platform.affiliates.approve', 'ability:platform.affiliates.manage');
        $this->assertRouteHasMiddleware('platform.affiliates.destroy', 'ability:platform.affiliates.manage');
    }

    private function assertRouteHasMiddleware(string $routeName, string $middleware): void
    {
        $route = app('router')->getRoutes()->getByName($routeName);

        $this->assertNotNull($route, sprintf('Route "%s" should exist.', $routeName));
        $this->assertContains(
            $middleware,
            $route->gatherMiddleware(),
            sprintf('Route "%s" is missing middleware "%s".', $routeName, $middleware)
        );
    }
}
