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
