<?php

namespace Tests\Unit;

use App\Models\BillingPlan;
use App\Models\School;
use Tests\TestCase;

class SchoolPricingTest extends TestCase
{
    public function testSchoolUsesAssignedBillingPlanRates(): void
    {
        $plan = new BillingPlan([
            'default_free_student_limit' => 50,
            'monthly_rate_per_student' => 500,
            'one_time_add_rate' => 1000,
            'affiliate_one_time_commission_per_student' => 200,
            'affiliate_monthly_commission_per_student' => 100,
        ]);

        $school = new School([
            'free_student_limit' => null,
        ]);
        $school->setRelation('billingPlan', $plan);

        $this->assertSame(50, $school->effectiveFreeStudentLimit());
        $this->assertSame(500, $school->effectiveMonthlyRate());
        $this->assertSame(1000, $school->effectiveOneTimeAddRate());
        $this->assertSame(200, $school->effectiveAffiliateOneTimeCommissionRate());
        $this->assertSame(100, $school->effectiveAffiliateMonthlyCommissionRate());
    }

    public function testSchoolFallsBackToConfiguredDefaultsWithoutBillingPlan(): void
    {
        config([
            'affiliate.one_time_per_new_billable_student' => 200,
            'affiliate.monthly_per_billable_student' => 100,
        ]);

        $school = new School([
            'free_student_limit' => null,
        ]);

        $this->assertSame(BillingPlan::DEFAULT_FREE_STUDENT_LIMIT, $school->effectiveFreeStudentLimit());
        $this->assertSame(BillingPlan::DEFAULT_MONTHLY_RATE_PER_STUDENT, $school->effectiveMonthlyRate());
        $this->assertSame(BillingPlan::DEFAULT_ONE_TIME_ADD_RATE, $school->effectiveOneTimeAddRate());
        $this->assertSame(200, $school->effectiveAffiliateOneTimeCommissionRate());
        $this->assertSame(100, $school->effectiveAffiliateMonthlyCommissionRate());
    }

    public function testSchoolSpecificFreeLimitOverrideStillApplies(): void
    {
        $plan = new BillingPlan([
            'default_free_student_limit' => 50,
            'monthly_rate_per_student' => 500,
            'one_time_add_rate' => 1000,
            'affiliate_one_time_commission_per_student' => 200,
            'affiliate_monthly_commission_per_student' => 100,
        ]);

        $school = new School([
            'free_student_limit' => 80,
        ]);
        $school->setRelation('billingPlan', $plan);

        $this->assertSame(80, $school->effectiveFreeStudentLimit());
        $this->assertSame(500, $school->effectiveMonthlyRate());
        $this->assertSame(1000, $school->effectiveOneTimeAddRate());
    }
}
