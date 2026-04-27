@extends('platform.layouts.master')

@section('page_title', 'Platform Dashboard')

@section('content')
<div class="row">
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-primary text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['total_schools']) }}</h3>
                <i class="icon-office font-size-24"></i>
            </div>
            <div>Total Schools</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-success text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['active_schools']) }}</h3>
                <i class="icon-checkmark4 font-size-24"></i>
            </div>
            <div>Active</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-indigo text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['trial_schools']) }}</h3>
                <i class="icon-hour-glass2 font-size-24"></i>
            </div>
            <div>Trial</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-warning text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['suspended_schools']) }}</h3>
                <i class="icon-blocked font-size-24"></i>
            </div>
            <div>Suspended</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-teal text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['teachers']) }}</h3>
                <i class="icon-user-tie font-size-24"></i>
            </div>
            <div>Total Teachers</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-info text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['students']) }}</h3>
                <i class="icon-users4 font-size-24"></i>
            </div>
            <div>Total Students</div>
        </div>
    </div>
</div>

<div class="row mb-2">
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-danger text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['risk_critical'] ?? 0) }}</h3>
                <i class="icon-warning22 font-size-24"></i>
            </div>
            <div>Billing Risk: Critical</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-warning text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['risk_high'] ?? 0) }}</h3>
                <i class="icon-alert font-size-24"></i>
            </div>
            <div>Billing Risk: High</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-info text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['risk_medium'] ?? 0) }}</h3>
                <i class="icon-info22 font-size-24"></i>
            </div>
            <div>Billing Risk: Medium</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-success text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['risk_low'] ?? 0) }}</h3>
                <i class="icon-checkmark-circle font-size-24"></i>
            </div>
            <div>Billing Risk: Low</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-secondary text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['risk_unknown'] ?? 0) }}</h3>
                <i class="icon-question3 font-size-24"></i>
            </div>
            <div>Billing Risk: Unknown</div>
        </div>
    </div>
</div>

<div class="row mb-2">
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-success text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['health_healthy'] ?? 0) }}</h3>
                <i class="icon-heart5 font-size-24"></i>
            </div>
            <div>Health: Healthy</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-info text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['health_watch'] ?? 0) }}</h3>
                <i class="icon-eye8 font-size-24"></i>
            </div>
            <div>Health: Watch</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-warning text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['health_at_risk'] ?? 0) }}</h3>
                <i class="icon-alert font-size-24"></i>
            </div>
            <div>Health: At Risk</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-danger text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['health_critical'] ?? 0) }}</h3>
                <i class="icon-warning22 font-size-24"></i>
            </div>
            <div>Health: Critical</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-2">
        <div class="card card-body bg-indigo text-white mb-3">
            <div class="d-flex justify-content-between">
                <h3 class="font-weight-semibold mb-0">{{ number_format($stats['health_average_score'] ?? 0) }}</h3>
                <i class="icon-stats-bars font-size-24"></i>
            </div>
            <div>Average Health Score</div>
        </div>
    </div>
</div>

{{-- Affiliate summary --}}
<div class="row mb-3">
    <div class="col-sm-4">
        <div class="card card-body bg-slate text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="font-weight-semibold mb-0">{{ number_format($stats['total_affiliates']) }}</h3>
                    <div>Total Affiliates</div>
                </div>
                <i class="icon-users2 font-size-24 opacity-75"></i>
            </div>
            <a href="{{ route('platform.affiliates.index') }}" class="text-white small mt-1 d-block">View all &rarr;</a>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card card-body bg-success text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="font-weight-semibold mb-0">{{ number_format($stats['approved_affiliates']) }}</h3>
                    <div>Approved Affiliates</div>
                </div>
                <i class="icon-checkmark3 font-size-24 opacity-75"></i>
            </div>
            <a href="{{ route('platform.affiliates.index', ['status' => 'approved']) }}" class="text-white small mt-1 d-block">View approved &rarr;</a>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card card-body {{ $stats['pending_affiliates'] > 0 ? 'bg-warning' : 'bg-secondary' }} text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="font-weight-semibold mb-0">{{ number_format($stats['pending_affiliates']) }}</h3>
                    <div>Pending Approval</div>
                </div>
                <i class="icon-hour-glass2 font-size-24 opacity-75"></i>
            </div>
            <a href="{{ route('platform.affiliates.index', ['status' => 'pending']) }}" class="text-white small mt-1 d-block">Review pending &rarr;</a>
        </div>
    </div>
</div>

@if ($pendingAffiliates->isNotEmpty())
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Pending Affiliate Applications</h5>
        <a href="{{ route('platform.affiliates.index', ['status' => 'pending']) }}" class="btn btn-sm btn-warning">View all</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Applied</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pendingAffiliates as $pa)
                    <tr>
                        <td>{{ $pa->name }}</td>
                        <td>{{ $pa->email }}</td>
                        <td>{{ $pa->phone }}</td>
                        <td>{{ $pa->created_at->diffForHumans() }}</td>
                        <td>
                            <a href="{{ route('platform.affiliates.show', $pa) }}" class="btn btn-sm btn-primary">Review</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header header-elements-inline">
        <h5 class="card-title">Registered Schools</h5>
    </div>

    <div class="card-body border-top-0">
        <div class="mb-3 d-flex flex-wrap" style="gap:8px;">
            <a href="{{ route('platform.dashboard', array_merge(request()->query(), ['at_risk' => '1'])) }}" class="btn btn-sm {{ request('at_risk') === '1' ? 'btn-dark' : 'btn-outline-dark' }}">At Risk Only (Critical + High)</a>
            <a href="{{ route('platform.dashboard', array_merge(request()->query(), ['risk' => 'critical'])) }}" class="btn btn-sm {{ request('risk') === 'critical' ? 'btn-danger' : 'btn-outline-danger' }}">Critical ({{ number_format($stats['risk_critical'] ?? 0) }})</a>
            <a href="{{ route('platform.dashboard', array_merge(request()->query(), ['risk' => 'high'])) }}" class="btn btn-sm {{ request('risk') === 'high' ? 'btn-warning' : 'btn-outline-warning' }}">High ({{ number_format($stats['risk_high'] ?? 0) }})</a>
            <a href="{{ route('platform.dashboard', array_merge(request()->query(), ['risk' => 'medium'])) }}" class="btn btn-sm {{ request('risk') === 'medium' ? 'btn-info' : 'btn-outline-info' }}">Medium ({{ number_format($stats['risk_medium'] ?? 0) }})</a>
            <a href="{{ route('platform.dashboard', array_merge(request()->query(), ['risk' => 'low'])) }}" class="btn btn-sm {{ request('risk') === 'low' ? 'btn-success' : 'btn-outline-success' }}">Low ({{ number_format($stats['risk_low'] ?? 0) }})</a>
            <a href="{{ route('platform.dashboard', array_merge(request()->query(), ['risk' => 'unknown'])) }}" class="btn btn-sm {{ request('risk') === 'unknown' ? 'btn-secondary' : 'btn-outline-secondary' }}">Unknown ({{ number_format($stats['risk_unknown'] ?? 0) }})</a>
            @if(request()->filled('risk'))
            <a href="{{ route('platform.dashboard', array_filter(request()->except('risk'))) }}" class="btn btn-sm btn-light">Clear Risk Filter</a>
            @endif
            @if(request()->boolean('at_risk'))
            <a href="{{ route('platform.dashboard', array_filter(request()->except('at_risk'))) }}" class="btn btn-sm btn-light">Clear At Risk</a>
            @endif
        </div>

        <form method="GET" action="{{ route('platform.dashboard') }}" class="mb-3">
            <div class="row">
                <div class="col-md-3 mb-2">
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
                    <select name="risk" class="form-control">
                        <option value="">All Risks</option>
                        <option value="critical" {{ request('risk') === 'critical' ? 'selected' : '' }}>Critical</option>
                        <option value="high" {{ request('risk') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="medium" {{ request('risk') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="low" {{ request('risk') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="unknown" {{ request('risk') === 'unknown' ? 'selected' : '' }}>Unknown</option>
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
                <div class="col-md-1 mb-2 d-flex align-items-center">
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" value="1" id="at_risk" name="at_risk" {{ request('at_risk') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label small" for="at_risk">At Risk</label>
                    </div>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('platform.dashboard') }}" class="btn btn-light">Reset</a>
                    <a href="{{ route('platform.schools.export', request()->except('page')) }}" class="btn btn-outline-secondary">Export CSV</a>
                    <a href="{{ route('platform.schools.export_at_risk_contacts', request()->except('page')) }}" class="btn btn-outline-danger">Export At-Risk Contacts</a>
                    <a href="{{ route('platform.schools.contact_gaps', request()->except('page')) }}" class="btn btn-warning">Contact Gaps Queue</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>School</th>
                        <th>Contact</th>
                        <th class="text-center">Teachers</th>
                        <th class="text-center">Students</th>
                        <th class="text-center">Total Users</th>
                        <th>Status</th>
                        <th>Health</th>
                        <th>Billing Risk</th>
                        <th>Subscription</th>
                        <th>Created</th>
                        <th class="text-center" style="min-width:250px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schools as $school)
                    @php
                    $sub = $school->subscription;
                    $failureCount = (int) ($sub->payment_failures_count ?? 0);
                    $graceEndsAt = optional($sub)->grace_period_ends_at;
                    $isGraceExpired = $graceEndsAt ? $graceEndsAt->lte(now()) : false;

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

                    $health = $schoolHealthById[$school->id] ?? [
                    'score' => 0,
                    'label' => 'Critical',
                    'badge' => 'danger',
                    'drivers' => [],
                    ];
                    @endphp
                    <tr>
                        <td>
                            <div class="font-weight-semibold">{{ $school->name }}</div>
                            <div class="text-muted small">{{ $school->slug }}</div>
                        </td>
                        <td>
                            <div>{{ $school->email ?: '-' }}</div>
                            <div class="text-muted small">{{ $school->phone ?: 'No phone' }}</div>
                            <div class="text-muted small">{{ $school->address ?: 'No address' }}</div>
                        </td>
                        <td class="text-center">{{ number_format($school->teachers_count) }}</td>
                        <td class="text-center">{{ number_format($school->students_count) }}</td>
                        <td class="text-center">{{ number_format($school->total_users_count) }}</td>
                        <td>
                            @if($school->status === 'active')
                            <span class="badge badge-success">Active</span>
                            @elseif($school->status === 'trial')
                            <span class="badge badge-primary">Trial</span>
                            @else
                            <span class="badge badge-warning">Suspended</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $health['badge'] }}">{{ $health['label'] }}</span>
                            <div class="text-muted small">{{ number_format((int) $health['score']) }}/100</div>
                            @if(!empty($health['drivers'][0]))
                            <div class="text-muted small">{{ $health['drivers'][0] }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $riskClass }}">{{ $riskLabel }}</span>
                        </td>
                        <td>
                            @if($school->subscription)
                            <span class="badge badge-{{ $school->subscription->isActive() ? 'success' : 'secondary' }}">
                                {{ ucfirst($school->subscription->status) }}
                            </span>
                            @else
                            <span class="badge badge-light">None</span>
                            @endif
                        </td>
                        <td>{{ optional($school->created_at)->format('d M Y') }}</td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-info" href="{{ route('platform.schools.show', $school) }}">View</a>

                            @if($school->status === 'suspended')
                            <form method="POST" action="{{ route('platform.schools.activate', $school) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-success">Activate</button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('platform.schools.suspend', $school) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-warning">Suspend</button>
                            </form>
                            @endif

                            <form method="POST" action="{{ route('platform.schools.destroy', $school) }}" class="d-inline" onsubmit="return confirm('Delete this school and all its data permanently?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted">No schools found.</td>
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