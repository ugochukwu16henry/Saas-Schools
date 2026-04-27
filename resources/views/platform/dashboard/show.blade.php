@extends('platform.layouts.master')

@section('page_title', 'School Detail')

@section('content')
<div class="mb-3">
    <a href="{{ route('platform.dashboard') }}" class="btn btn-light btn-sm"><i class="icon-arrow-left8"></i> Back</a>
</div>

<div class="card">
    <div class="card-header header-elements-inline">
        <h5 class="card-title mb-0">{{ $school->name }}</h5>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="font-weight-semibold">School Profile</h6>
                <table class="table table-borderless table-sm">
                    <tr>
                        <th style="width:180px;">Slug</th>
                        <td>{{ $school->slug }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $school->email ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td>{{ $school->phone ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td>{{ $school->address ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($school->status === 'active')
                            <span class="badge badge-success">Active</span>
                            @elseif($school->status === 'trial')
                            <span class="badge badge-primary">Trial</span>
                            @else
                            <span class="badge badge-warning">Suspended</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Free Student Limit</th>
                        <td>{{ number_format($school->free_student_limit) }}</td>
                    </tr>
                    <tr>
                        <th>Created</th>
                        <td>{{ optional($school->created_at)->format('d M Y h:i A') }}</td>
                    </tr>
                </table>
            </div>

            <div class="col-md-6">
                <h6 class="font-weight-semibold">Usage & Billing</h6>
                @php
                $subscription = $school->subscription;
                $failureCount = (int) ($subscription->payment_failures_count ?? 0);
                $graceEndsAt = optional($subscription)->grace_period_ends_at;
                $isGraceExpired = $graceEndsAt ? $graceEndsAt->lte(now()) : false;

                if (!$subscription) {
                $billingRiskLabel = 'Unknown';
                $billingRiskClass = 'secondary';
                } elseif (in_array((string) $subscription->status, ['expired', 'cancelled'], true) || $school->status === 'suspended') {
                $billingRiskLabel = 'Critical';
                $billingRiskClass = 'danger';
                } elseif ($isGraceExpired || $failureCount >= 2) {
                $billingRiskLabel = 'High';
                $billingRiskClass = 'warning';
                } elseif ($failureCount >= 1) {
                $billingRiskLabel = 'Medium';
                $billingRiskClass = 'info';
                } else {
                $billingRiskLabel = 'Low';
                $billingRiskClass = 'success';
                }
                @endphp
                <table class="table table-borderless table-sm">
                    <tr>
                        <th style="width:220px;">Billing Risk</th>
                        <td><span class="badge badge-{{ $billingRiskClass }}">{{ $billingRiskLabel }}</span></td>
                    </tr>
                    <tr>
                        <th style="width:220px;">Total Users</th>
                        <td>{{ number_format($school->total_users_count) }}</td>
                    </tr>
                    <tr>
                        <th>Students</th>
                        <td>{{ number_format($school->students_count) }}</td>
                    </tr>
                    <tr>
                        <th>Teachers</th>
                        <td>{{ number_format($school->teachers_count) }}</td>
                    </tr>
                    <tr>
                        <th>Admins (school-side)</th>
                        <td>{{ number_format($school->admins_count) }}</td>
                    </tr>
                    <tr>
                        <th>Billable Students</th>
                        <td>{{ number_format(max(0, $school->students_count - $school->free_student_limit)) }}</td>
                    </tr>

                    @if($school->subscription)
                    <tr>
                        <th>Subscription Status</th>
                        <td>
                            @php $subStatus = (string) $school->subscription->status; @endphp
                            @if($subStatus === 'active')
                            <span class="badge badge-success">Active</span>
                            @elseif($subStatus === 'trialling')
                            <span class="badge badge-primary">Trialling</span>
                            @elseif($subStatus === 'expired')
                            <span class="badge badge-danger">Expired</span>
                            @elseif($subStatus === 'cancelled')
                            <span class="badge badge-warning">Cancelled</span>
                            @else
                            <span class="badge badge-secondary">{{ ucfirst($subStatus) }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Trial Ends</th>
                        <td>{{ optional($school->subscription->trial_ends_at)->format('d M Y h:i A') ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Next Payment Date</th>
                        <td>{{ optional($school->subscription->next_payment_date)->format('d M Y') ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Payment Failure Count</th>
                        <td>
                            {{ number_format((int) ($school->subscription->payment_failures_count ?? 0)) }}
                            @if((int) ($school->subscription->payment_failures_count ?? 0) >= 2)
                            <span class="badge badge-warning ml-1">Escalating</span>
                            @elseif((int) ($school->subscription->payment_failures_count ?? 0) >= 1)
                            <span class="badge badge-info ml-1">Warning</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Grace Period Ends</th>
                        <td>
                            {{ optional($school->subscription->grace_period_ends_at)->format('d M Y h:i A') ?: '-' }}
                            @if(optional($school->subscription->grace_period_ends_at) && $school->subscription->grace_period_ends_at->lte(now()))
                            <span class="badge badge-danger ml-1">Expired</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Last Payment Failure</th>
                        <td>{{ optional($school->subscription->last_payment_failed_at)->format('d M Y h:i A') ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Failure Reason</th>
                        <td>{{ $school->subscription->last_payment_failure_reason ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Last Payment Reference</th>
                        <td>{{ $school->subscription->last_payment_reference ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Trial Warning (7d)</th>
                        <td>{{ optional($school->subscription->trial_warning_7d_sent_at)->format('d M Y h:i A') ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Trial Warning (1d)</th>
                        <td>{{ optional($school->subscription->trial_warning_1d_sent_at)->format('d M Y h:i A') ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Paystack Customer Code</th>
                        <td>{{ $school->subscription->paystack_customer_code ?: '-' }}</td>
                    </tr>
                    @else
                    <tr>
                        <th>Subscription</th>
                        <td>No subscription record yet</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <hr>

        {{-- ── Plan Override ──────────────────────────────────────────────── --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-header bg-light py-2">
                        <h6 class="card-title mb-0">Plan Override — Free Student Limit</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Students up to this limit are free; any above are billed at ₦100/student/month.
                            Default is 0 (all students billed).
                        </p>
                        <form method="POST" action="{{ route('platform.schools.update_plan', $school) }}" class="form-inline">
                            @csrf
                            @method('PATCH')
                            <div class="input-group" style="max-width:260px;">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Free Students</span>
                                </div>
                                <input type="number" name="free_student_limit"
                                    class="form-control @error('free_student_limit') is-invalid @enderror"
                                    value="{{ old('free_student_limit', $school->free_student_limit) }}"
                                    min="0" max="100000" required>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">Update</button>
                                </div>
                            </div>
                            @error('free_student_limit')
                            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                            @enderror
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <hr>

        <div class="d-flex flex-wrap" style="gap:8px;">
            @if($school->status === 'suspended')
            <form method="POST" action="{{ route('platform.schools.activate', $school) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-success">Activate School</button>
            </form>
            @else
            <form method="POST" action="{{ route('platform.schools.suspend', $school) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-warning">Suspend School</button>
            </form>
            @endif

            <form method="POST" action="{{ route('platform.schools.destroy', $school) }}" onsubmit="return confirm('Delete this school and ALL tenant data permanently?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete School Permanently</button>
            </form>
        </div>
    </div>
</div>
@endsection