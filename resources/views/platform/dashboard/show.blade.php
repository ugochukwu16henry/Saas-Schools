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
                    <tr><th style="width:180px;">Slug</th><td>{{ $school->slug }}</td></tr>
                    <tr><th>Email</th><td>{{ $school->email ?: '-' }}</td></tr>
                    <tr><th>Phone</th><td>{{ $school->phone ?: '-' }}</td></tr>
                    <tr><th>Address</th><td>{{ $school->address ?: '-' }}</td></tr>
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
                    <tr><th>Free Student Limit</th><td>{{ number_format($school->free_student_limit) }}</td></tr>
                    <tr><th>Created</th><td>{{ optional($school->created_at)->format('d M Y h:i A') }}</td></tr>
                </table>
            </div>

            <div class="col-md-6">
                <h6 class="font-weight-semibold">Usage & Billing</h6>
                <table class="table table-borderless table-sm">
                    <tr><th style="width:220px;">Total Users</th><td>{{ number_format($school->total_users_count) }}</td></tr>
                    <tr><th>Students</th><td>{{ number_format($school->students_count) }}</td></tr>
                    <tr><th>Teachers</th><td>{{ number_format($school->teachers_count) }}</td></tr>
                    <tr><th>Admins (school-side)</th><td>{{ number_format($school->admins_count) }}</td></tr>
                    <tr><th>Billable Students</th><td>{{ number_format(max(0, $school->students_count - $school->free_student_limit)) }}</td></tr>

                    @if($school->subscription)
                        <tr><th>Subscription Status</th><td>{{ ucfirst($school->subscription->status) }}</td></tr>
                        <tr><th>Next Payment Date</th><td>{{ optional($school->subscription->next_payment_date)->format('d M Y') ?: '-' }}</td></tr>
                        <tr><th>Paystack Customer Code</th><td>{{ $school->subscription->paystack_customer_code ?: '-' }}</td></tr>
                    @else
                        <tr><th>Subscription</th><td>No subscription record yet</td></tr>
                    @endif
                </table>
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
