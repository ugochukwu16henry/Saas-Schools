@extends('platform.layouts.master')

@section('page_title', 'At-Risk Contact Gaps')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">At-Risk Schools Missing Contact Details</h5>
        <div>
            <a href="{{ route('platform.dashboard') }}" class="btn btn-sm btn-light">Back to Dashboard</a>
            <a href="{{ route('platform.schools.export_at_risk_contacts', request()->except('page')) }}" class="btn btn-sm btn-outline-danger">Export At-Risk Contacts</a>
        </div>
    </div>

    <div class="card-body border-top-0">
        <form method="GET" action="{{ route('platform.schools.contact_gaps') }}" class="mb-3">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <input type="text" name="q" class="form-control" placeholder="Search by name, email, phone or slug" value="{{ request('q') }}">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Trial</option>
                        <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="sort" class="form-control">
                        <option value="risk" {{ request('sort', 'risk') === 'risk' ? 'selected' : '' }}>Sort: Risk Priority</option>
                        <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Sort: Newest</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Sort: Oldest</option>
                        <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Sort: Name A-Z</option>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('platform.schools.contact_gaps') }}" class="btn btn-light">Reset</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>School</th>
                        <th>Billing Risk</th>
                        <th>Contact Gaps</th>
                        <th>Subscription</th>
                        <th class="text-center">Payment Failures</th>
                        <th>Grace Ends</th>
                        <th>Created</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schools as $school)
                    @php
                    $sub = $school->subscription;
                    $failureCount = (int) ($sub->payment_failures_count ?? 0);
                    $graceEndsAt = optional($sub)->grace_period_ends_at;
                    $isGraceExpired = $graceEndsAt ? $graceEndsAt->lte(now()) : false;
                    $hasEmail = filled($school->email);
                    $hasPhone = filled($school->phone);
                    $isUnreachable = !$hasEmail && !$hasPhone;
                    $smsPhone = preg_replace('/\s+/', '', (string) $school->phone);

                    if (!$sub) {
                    $riskLabel = 'Unknown';
                    $riskClass = 'secondary';
                    } elseif (in_array((string) $sub->status, ['expired', 'cancelled'], true) || $school->status === 'suspended') {
                    $riskLabel = 'Critical';
                    $riskClass = 'danger';
                    } elseif ($isGraceExpired || $failureCount >= 2) {
                    $riskLabel = 'High';
                    $riskClass = 'warning';
                    } elseif ($failureCount >= 1) {
                    $riskLabel = 'Medium';
                    $riskClass = 'info';
                    } else {
                    $riskLabel = 'Low';
                    $riskClass = 'success';
                    }
                    @endphp
                    <tr class="{{ $isUnreachable ? 'table-danger' : '' }}">
                        <td>
                            <div class="font-weight-semibold">{{ $school->name }}</div>
                            <div class="text-muted small">{{ $school->slug }}</div>
                            <div class="text-muted small">Status: {{ ucfirst($school->status) }}</div>
                            @if($isUnreachable)
                            <div class="text-danger small font-weight-semibold">Unreachable: no email or phone</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $riskClass }}">{{ $riskLabel }}</span>
                        </td>
                        <td>
                            <div>Email: {{ $hasEmail ? $school->email : 'MISSING' }}</div>
                            <div>Phone: {{ $hasPhone ? $school->phone : 'MISSING' }}</div>
                        </td>
                        <td>
                            @if($sub)
                            <span class="badge badge-{{ $sub->isActive() ? 'success' : 'secondary' }}">{{ ucfirst($sub->status) }}</span>
                            @else
                            <span class="badge badge-light">None</span>
                            @endif
                        </td>
                        <td class="text-center">{{ number_format((int) ($sub->payment_failures_count ?? 0)) }}</td>
                        <td>{{ optional($sub?->grace_period_ends_at)->format('d M Y, H:i') ?: '-' }}</td>
                        <td>{{ optional($school->created_at)->format('d M Y') }}</td>
                        <td class="text-center">
                            @if($hasEmail)
                            <a class="btn btn-sm btn-outline-primary mb-1" href="mailto:{{ $school->email }}?subject=Billing%20Follow-up">Email</a>
                            @endif
                            @if($hasPhone)
                            <a class="btn btn-sm btn-outline-success mb-1" href="sms:{{ $smsPhone }}?body=Hello%2C%20your%20school%20account%20requires%20billing%20attention.">SMS</a>
                            @endif
                            <a class="btn btn-sm btn-info mb-1" href="{{ route('platform.schools.show', $school) }}">View School</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No at-risk schools with contact gaps found for current filters.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $schools->links() }}
        </div>
    </div>
</div>
@endsection