@extends('layouts.master')
@section('page_title', 'My Dashboard')
@section('content')

@if(Qs::userIsTeamSA() && !empty($billingContext))
<div class="alert alert-info alert-styled-left alert-dismissible mb-3">
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    <div class="d-flex flex-wrap align-items-center" style="gap:10px;">
        <span class="font-weight-semibold">Effective Billing Plan:</span>
        <span class="badge badge-primary">{{ $billingContext['plan_name'] }}</span>
        <span class="text-muted small">Free: {{ number_format($billingContext['free_limit']) }} students</span>
        <span class="text-muted small">Monthly: ₦{{ number_format($billingContext['monthly_rate']) }}/student</span>
        <span class="text-muted small">One-time: ₦{{ number_format($billingContext['one_time_rate']) }}/new billable student</span>
        <a href="{{ route('billing.status') }}" class="btn btn-sm btn-outline-primary">View Billing Details</a>
    </div>
</div>
@endif

@if(Qs::userIsTeamSA())
<div class="row">
    <div class="col-sm-6 col-xl-3">
        <div class="card card-body bg-blue-400 has-bg-image">
            <div class="media">
                <div class="media-body">
                    <h3 class="mb-0">{{ $users->where('user_type', 'student')->count() }}</h3>
                    <span class="text-uppercase font-size-xs font-weight-bold">Total Students</span>
                </div>

                <div class="ml-3 align-self-center">
                    <i class="icon-users4 icon-3x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card card-body bg-danger-400 has-bg-image">
            <div class="media">
                <div class="media-body">
                    <h3 class="mb-0">{{ $users->where('user_type', 'teacher')->count() }}</h3>
                    <span class="text-uppercase font-size-xs">Total Teachers</span>
                </div>

                <div class="ml-3 align-self-center">
                    <i class="icon-users2 icon-3x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card card-body bg-success-400 has-bg-image">
            <div class="media">
                <div class="mr-3 align-self-center">
                    <i class="icon-pointer icon-3x opacity-75"></i>
                </div>

                <div class="media-body text-right">
                    <h3 class="mb-0">{{ $users->where('user_type', 'admin')->count() }}</h3>
                    <span class="text-uppercase font-size-xs">Total Administrators</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card card-body bg-indigo-400 has-bg-image">
            <div class="media">
                <div class="mr-3 align-self-center">
                    <i class="icon-user icon-3x opacity-75"></i>
                </div>

                <div class="media-body text-right">
                    <h3 class="mb-0">{{ $users->where('user_type', 'parent')->count() }}</h3>
                    <span class="text-uppercase font-size-xs">Total Parents</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{--Events Calendar Begins--}}
<div class="card">
    <div class="card-header header-elements-inline">
        <h5 class="card-title">School Events Calendar</h5>
        {!! Qs::getPanelOptions() !!}
    </div>

    <div class="card-body">
        <div class="fullcalendar-basic"></div>
    </div>
</div>
{{--Events Calendar Ends--}}
@endsection