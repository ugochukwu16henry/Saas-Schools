@extends('layouts.master')

@section('page_title', 'Billing & Subscription')

@section('content')

@php
$statusLabel = 'Unknown';
$statusClass = 'secondary';
if ($sub) {
$statusLabel = ucfirst($sub->status);
$statusClass = match($sub->status) {
'active' => 'success',
'trialling' => 'info',
'expired' => 'danger',
'cancelled' => 'danger',
default => 'secondary',
};
}

$isSuspended = $school->status === 'suspended';
$isActive = $sub && $sub->status === 'active';
$isTrialling = $sub && $sub->status === 'trialling';
$isExpired = $sub && in_array($sub->status, ['expired', 'cancelled']);
$hasFailures = $sub && $sub->payment_failures_count > 0;
$trialDaysLeft = ($isTrialling && $sub->trial_ends_at) ? max(0, (int) now()->diffInDays($sub->trial_ends_at, false)) : null;
@endphp

<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="font-weight-semibold mb-0">Billing &amp; Subscription</h4>
    <span class="badge badge-light">Plan: {{ $planName }}</span>
</div>

{{-- ── Suspension / failure banner ───────────────────────────────────── --}}
@if($isSuspended)
<div class="alert alert-danger">
    <strong>Your school is currently suspended.</strong>
    Please renew your subscription to restore access for all users.
    <a href="{{ route('billing.initialize') }}" class="btn btn-sm btn-danger ml-3">Pay Now</a>
</div>
@elseif($hasFailures && !$isActive)
<div class="alert alert-warning">
    <strong>Payment issue detected.</strong>
    {{ $sub->payment_failures_count }} failed attempt(s). Last reason:
    <em>{{ $sub->last_payment_failure_reason ?? 'Unknown' }}</em>.
    @if($sub->grace_period_ends_at)
    Grace period ends {{ $sub->grace_period_ends_at->diffForHumans() }}.
    @endif
    <a href="{{ route('billing.initialize') }}" class="btn btn-sm btn-warning ml-3">Retry Payment</a>
</div>
@elseif($isTrialling && $trialDaysLeft !== null && $trialDaysLeft <= 7)
    <div class="alert alert-info">
    <strong>Trial ending soon.</strong>
    Your free trial expires in <strong>{{ $trialDaysLeft }} day(s)</strong>.
    Subscribe now to avoid interruption.
    <a href="{{ route('billing.initialize') }}" class="btn btn-sm btn-info ml-3">Subscribe Now</a>
    </div>
    @endif

    {{-- ── Status summary cards ───────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Subscription Status</div>
                        <div class="font-size-lg font-weight-bold mt-1">
                            <span class="badge badge-{{ $statusClass }} px-3 py-2">{{ $statusLabel }}</span>
                        </div>
                    </div>
                    <i class="icon-credit-card2 text-muted" style="font-size:2rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Total Students</div>
                        <div class="font-size-lg font-weight-bold mt-1">{{ number_format($studentCount) }}</div>
                    </div>
                    <i class="icon-users text-muted" style="font-size:2rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Free Student Allowance</div>
                        <div class="font-size-lg font-weight-bold mt-1">{{ number_format($freeLimit) }}</div>
                    </div>
                    <i class="icon-gift text-muted" style="font-size:2rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Billable Students</div>
                        <div class="font-size-lg font-weight-bold mt-1">{{ number_format($billableCount) }}</div>
                    </div>
                    <i class="icon-calculator text-muted" style="font-size:2rem;"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Billing details + actions ─────────────────────────────────────── --}}
    <div class="row mt-2">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">Subscription Details</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted" style="width:45%">Status</th>
                                <td><span class="badge badge-{{ $statusClass }}">{{ $statusLabel }}</span></td>
                            </tr>
                            @if($isTrialling && $sub->trial_ends_at)
                            <tr>
                                <th class="text-muted">Trial Ends</th>
                                <td>
                                    {{ $sub->trial_ends_at->format('d M Y') }}
                                    @if($trialDaysLeft !== null)
                                    <span class="text-{{ $trialDaysLeft <= 3 ? 'danger' : ($trialDaysLeft <= 7 ? 'warning' : 'muted') }} small ml-1">
                                        ({{ $trialDaysLeft }} day(s) left)
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @endif
                            @if($isActive && $sub->next_payment_date)
                            <tr>
                                <th class="text-muted">Next Payment</th>
                                <td>{{ $sub->next_payment_date->format('d M Y') }}</td>
                            </tr>
                            @endif
                            @if($sub && $sub->billed_students)
                            <tr>
                                <th class="text-muted">Billed Students</th>
                                <td>{{ number_format($sub->billed_students) }}</td>
                            </tr>
                            @endif
                            @if($hasFailures)
                            <tr>
                                <th class="text-muted">Payment Failures</th>
                                <td class="text-danger font-weight-semibold">{{ $sub->payment_failures_count }}</td>
                            </tr>
                            @if($sub->last_payment_failed_at)
                            <tr>
                                <th class="text-muted">Last Failure</th>
                                <td>
                                    {{ $sub->last_payment_failed_at->format('d M Y H:i') }}
                                    @if($sub->last_payment_failure_reason)
                                    <div class="text-muted small">{{ $sub->last_payment_failure_reason }}</div>
                                    @endif
                                </td>
                            </tr>
                            @endif
                            @if($sub->grace_period_ends_at)
                            <tr>
                                <th class="text-muted">Grace Period Ends</th>
                                <td class="text-warning font-weight-semibold">{{ $sub->grace_period_ends_at->format('d M Y') }}</td>
                            </tr>
                            @endif
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">Current Billing Calculation</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted" style="width:65%">Total Students</th>
                                <td>{{ number_format($studentCount) }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Free Allowance</th>
                                <td>− {{ number_format($freeLimit) }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Billable Students</th>
                                <td class="font-weight-semibold">{{ number_format($billableCount) }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Monthly Subscription (₦{{ number_format($monthlyRate) }}/student)</th>
                                <td>₦{{ number_format($monthlyAmount) }}</td>
                            </tr>
                            @if($newlyAddedCount > 0)
                            <tr>
                                <th class="text-muted">One-time New Students Fee ({{ $newlyAddedCount }} × ₦{{ number_format($oneTimeRate) }})</th>
                                <td>₦{{ number_format($oneTimeAmount) }}</td>
                            </tr>
                            @endif
                            <tr class="border-top">
                                <th>Total Due Now</th>
                                <td class="font-weight-bold text-teal font-size-base">₦{{ number_format($totalDue) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent d-flex gap-2">
                    @if($totalDue > 0)
                    <a href="{{ route('billing.initialize') }}" class="btn btn-success">
                        <i class="icon-credit-card mr-1"></i>
                        {{ $isActive ? 'Pay Monthly Bill' : 'Subscribe / Pay Now' }}
                    </a>
                    @else
                    <span class="badge badge-success px-3 py-2"><i class="icon-checkmark mr-1"></i> No payment due</span>
                    @endif
                    @if(!$isActive && !$isTrialling)
                    <a href="{{ route('billing.prompt') }}" class="btn btn-outline-secondary">View Billing Info</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @endsection