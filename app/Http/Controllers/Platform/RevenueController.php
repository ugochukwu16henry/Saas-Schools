<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RevenueController extends Controller
{
    public function index(Request $request)
    {
        // ── Subscription status counts ──────────────────────────────────────
        $statusCounts = SchoolSubscription::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $activeCount    = (int) ($statusCounts['active']    ?? 0);
        $triallingCount = (int) ($statusCounts['trialling'] ?? 0);
        $expiredCount   = (int) ($statusCounts['expired']   ?? 0);
        $cancelledCount = (int) ($statusCounts['cancelled'] ?? 0);

        $activeSubs = SchoolSubscription::query()
            ->with('school.billingPlan')
            ->where('status', 'active')
            ->get();

        // ── MRR: active subscribers × billed_students × each school's plan rate ─
        $mrr = (int) $activeSubs->sum(function ($sub) {
            $school = $sub->school;
            $rate = $school ? $school->effectiveMonthlyRate() : 100;

            return ((int) $sub->billed_students) * $rate;
        });

        $arr = $mrr * 12;

        // ── Total billed students across all active subscriptions ────────────
        $totalBilledStudents = (int) SchoolSubscription::where('status', 'active')
            ->sum('billed_students');

        // ── Schools without a subscription (no billing record at all) ────────
        $noSubscriptionCount = School::doesntHave('subscription')->count();

        // ── At-risk count (suspended or expired/cancelled sub) ───────────────
        $atRiskCount = School::query()
            ->where(function ($q) {
                $q->where('status', 'suspended')
                    ->orWhereHas('subscription', function ($sq) {
                        $sq->whereIn('status', ['expired', 'cancelled']);
                    });
            })
            ->count();

        // ── Monthly trend: new subscriptions created per month (last 12m) ────
        $monthlyGrowth = SchoolSubscription::query()
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('count(*) as new_subs')
            )
            ->where('created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // ── Churn: subscriptions that became expired/cancelled per month ─────
        $monthlyChurn = SchoolSubscription::query()
            ->select(
                DB::raw("DATE_FORMAT(updated_at, '%Y-%m') as month"),
                DB::raw('count(*) as churned')
            )
            ->whereIn('status', ['expired', 'cancelled'])
            ->where('updated_at', '>=', now()->subMonths(12)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // ── Top paying schools (by billed_students) ──────────────────────────
        $topSchools = SchoolSubscription::query()
            ->with(['school:id,name,slug,email,billing_plan_id', 'school.billingPlan:id,monthly_rate_per_student'])
            ->where('status', 'active')
            ->orderByDesc('billed_students')
            ->limit(10)
            ->get();

        $displayRateText = 'Plan-based rates';

        return view('platform.revenue.index', compact(
            'activeCount',
            'triallingCount',
            'expiredCount',
            'cancelledCount',
            'mrr',
            'arr',
            'totalBilledStudents',
            'noSubscriptionCount',
            'atRiskCount',
            'monthlyGrowth',
            'monthlyChurn',
            'topSchools',
            'displayRateText',
        ));
    }
}
