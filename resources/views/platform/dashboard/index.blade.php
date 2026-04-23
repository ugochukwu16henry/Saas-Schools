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
        <form method="GET" action="{{ route('platform.dashboard') }}" class="mb-3">
            <div class="row">
                <div class="col-md-5 mb-2">
                    <input type="text" name="q" class="form-control" placeholder="Search by name, email, phone or slug" value="{{ request('q') }}">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Trial</option>
                        <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('platform.dashboard') }}" class="btn btn-light">Reset</a>
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
                    <th>Subscription</th>
                    <th>Created</th>
                    <th class="text-center" style="min-width:250px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($schools as $school)
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
                        <td colspan="9" class="text-center text-muted">No schools found.</td>
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
