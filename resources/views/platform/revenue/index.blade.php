@extends('platform.layouts.master')

@section('page_title', 'Revenue Report')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="font-weight-semibold mb-0">Revenue Report</h4>
</div>

{{-- ── Row 1: Key revenue metrics ────────────────────────────────── --}}
<div class="row">
    <div class="col-sm-6 col-xl-3">
        <div class="card bg-teal text-white">
            <div class="card-body">
                <h6 class="font-weight-semibold mb-0">Monthly Recurring Revenue</h6>
                <h2 class="font-weight-bold mt-2 mb-0">₦{{ number_format($mrr) }}</h2>
                <small class="opacity-75">MRR — active subscribers</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card bg-blue text-white">
            <div class="card-body">
                <h6 class="font-weight-semibold mb-0">Annual Recurring Revenue</h6>
                <h2 class="font-weight-bold mt-2 mb-0">₦{{ number_format($arr) }}</h2>
                <small class="opacity-75">ARR — MRR × 12</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card bg-indigo text-white">
            <div class="card-body">
                <h6 class="font-weight-semibold mb-0">Billed Students</h6>
                <h2 class="font-weight-bold mt-2 mb-0">{{ number_format($totalBilledStudents) }}</h2>
                <small class="opacity-75">Across all active schools</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card bg-slate text-white">
            <div class="card-body">
                <h6 class="font-weight-semibold mb-0">Per-Student Rate</h6>
                <h2 class="font-weight-bold mt-2 mb-0">{{ $displayRateText }}</h2>
                <small class="opacity-75">Configured in Billing Plans</small>
            </div>
        </div>
    </div>
</div>

{{-- ── Row 2: Subscription pipeline ──────────────────────────────── --}}
<div class="row mt-2">
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body py-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">Active</div>
                    <div class="font-size-lg font-weight-bold">{{ number_format($activeCount) }}</div>
                </div>
                <span class="badge badge-success badge-pill px-3">Paying</span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body py-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">Trialling</div>
                    <div class="font-size-lg font-weight-bold">{{ number_format($triallingCount) }}</div>
                </div>
                <span class="badge badge-info badge-pill px-3">Trial</span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body py-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">Expired / Cancelled</div>
                    <div class="font-size-lg font-weight-bold">{{ number_format($expiredCount + $cancelledCount) }}</div>
                </div>
                <span class="badge badge-danger badge-pill px-3">Churned</span>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body py-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">No Subscription</div>
                    <div class="font-size-lg font-weight-bold">{{ number_format($noSubscriptionCount) }}</div>
                </div>
                <span class="badge badge-warning badge-pill px-3">Unbilled</span>
            </div>
        </div>
    </div>
</div>

{{-- ── Row 3: Monthly trend table + top schools ──────────────────── --}}
<div class="row mt-2">

    {{-- Monthly new subscriptions vs churn --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">Monthly Subscription Trend (Last 12 Months)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Month</th>
                            <th class="text-center">New Subs</th>
                            <th class="text-center">Churned</th>
                            <th class="text-center">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        // Build a 12-month scaffold
                        $growthMap = $monthlyGrowth->pluck('new_subs', 'month')->toArray();
                        $churnMap = $monthlyChurn->pluck('churned', 'month')->toArray();
                        $months = [];
                        for ($i = 11; $i >= 0; $i--) {
                        $months[] = now()->subMonths($i)->format('Y-m');
                        }
                        @endphp
                        @foreach($months as $m)
                        @php
                        $newSubs = $growthMap[$m] ?? 0;
                        $churned = $churnMap[$m] ?? 0;
                        $net = $newSubs - $churned;
                        @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $m)->format('M Y') }}</td>
                            <td class="text-center">
                                @if($newSubs > 0)
                                <span class="badge badge-success">+{{ $newSubs }}</span>
                                @else
                                <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($churned > 0)
                                <span class="badge badge-danger">{{ $churned }}</span>
                                @else
                                <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-center font-weight-semibold {{ $net > 0 ? 'text-success' : ($net < 0 ? 'text-danger' : 'text-muted') }}">
                                {{ $net > 0 ? '+' : '' }}{{ $net }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Top paying schools --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="card-title mb-0">Top Paying Schools (by Billed Students)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>School</th>
                            <th class="text-center">Students</th>
                            <th class="text-right">Monthly Rev</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topSchools as $i => $sub)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td>
                                <a href="{{ route('platform.schools.show', $sub->school) }}" class="font-weight-semibold">
                                    {{ $sub->school->name ?? '—' }}
                                </a>
                                @if($sub->school && $sub->school->email)
                                <div class="text-muted small">{{ $sub->school->email }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ number_format($sub->billed_students) }}</td>
                            <td class="text-right font-weight-semibold text-teal">
                                ₦{{ number_format($sub->billed_students * (($sub->school && $sub->school->billingPlan) ? $sub->school->billingPlan->monthly_rate_per_student : 100)) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">No active subscriptions yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($topSchools->count())
                    <tfoot class="thead-light">
                        <tr>
                            <th colspan="3" class="text-right">Total MRR</th>
                            <th class="text-right text-teal">₦{{ number_format($mrr) }}</th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

</div>

@endsection