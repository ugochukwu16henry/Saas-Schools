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

@if(Qs::userIsStudent())
<div class="card mb-3">
    <div class="card-header header-elements-inline">
        <h6 class="card-title font-weight-semibold">My Transfer History</h6>
    </div>
    <div class="card-body">
        @if(!empty($studentQrToken))
        <div class="alert alert-info alert-styled-left py-2">
            <span class="font-weight-semibold">Student Verification Link:</span>
            <a href="{{ route('students.verify.public', $studentQrToken) }}" target="_blank">
                {{ route('students.verify.public', $studentQrToken) }}
            </a>
        </div>
        @endif

        <div class="mb-3">
            <a href="{{ route('students.transcript.show', auth()->id()) }}" class="btn btn-outline-primary btn-sm" target="_blank">View Transcript</a>
            <a href="{{ route('students.transcript.download', auth()->id()) }}" class="btn btn-primary btn-sm" target="_blank">Download Transcript PDF</a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>From School</th>
                        <th>To School</th>
                        <th>Transferred At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($studentTransferHistory ?? collect()) as $transfer)
                    <tr>
                        <td>{{ optional($transfer->fromSchool)->name }}</td>
                        <td>{{ optional($transfer->toSchool)->name }}</td>
                        <td>{{ optional($transfer->transferred_at)->toDateTimeString() }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted">No transfer history available.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@if(Qs::userIsTeamSA() && isset($recentlyReceivedTransfers))
<div class="row mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card card-body bg-primary-400 has-bg-image">
            <div class="media">
                <div class="media-body">
                    <h3 class="mb-0">{{ data_get($transferKpis ?? [], 'pending_incoming', 0) }}</h3>
                    <span class="text-uppercase font-size-xs">Pending Incoming Transfers</span>
                </div>
                <div class="ml-3 align-self-center">
                    <i class="icon-download4 icon-3x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card card-body bg-warning-400 has-bg-image">
            <div class="media">
                <div class="media-body">
                    <h3 class="mb-0">{{ data_get($transferKpis ?? [], 'pending_outgoing', 0) }}</h3>
                    <span class="text-uppercase font-size-xs">Pending Outgoing Transfers</span>
                </div>
                <div class="ml-3 align-self-center">
                    <i class="icon-upload4 icon-3x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card card-body bg-success-400 has-bg-image">
            <div class="media">
                <div class="media-body">
                    <h3 class="mb-0">{{ data_get($transferKpis ?? [], 'accepted_last_30', 0) }}</h3>
                    <span class="text-uppercase font-size-xs">Accepted (Last 30 Days)</span>
                </div>
                <div class="ml-3 align-self-center">
                    <i class="icon-checkmark4 icon-3x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card card-body bg-indigo-400 has-bg-image">
            <div class="media">
                <div class="media-body">
                    <h3 class="mb-0">
                        @if(!is_null(data_get($transferKpis ?? [], 'avg_acceptance_hours_last_30')))
                        {{ number_format((float) data_get($transferKpis, 'avg_acceptance_hours_last_30'), 1) }}h
                        @else
                        N/A
                        @endif
                    </h3>
                    <span class="text-uppercase font-size-xs">Avg Acceptance Time (30d)</span>
                </div>
                <div class="ml-3 align-self-center">
                    <i class="icon-history icon-3x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header header-elements-inline">
        <h6 class="card-title font-weight-semibold">Transfer KPI Drilldown</h6>
        <div class="d-flex align-items-center" style="gap:8px;">
            <a href="{{ route('dashboard', array_merge(request()->query(), ['transfer_kpi_scope' => 'incoming'])) }}" class="btn btn-sm {{ data_get($transferKpiDrilldown ?? [], 'scope', 'incoming') === 'incoming' ? 'btn-primary' : 'btn-light' }}">Incoming</a>
            <a href="{{ route('dashboard', array_merge(request()->query(), ['transfer_kpi_scope' => 'outgoing'])) }}" class="btn btn-sm {{ data_get($transferKpiDrilldown ?? [], 'scope', 'incoming') === 'outgoing' ? 'btn-primary' : 'btn-light' }}">Outgoing</a>
            <a href="{{ route('dashboard', array_merge(request()->query(), ['transfer_kpi_scope' => 'all'])) }}" class="btn btn-sm {{ data_get($transferKpiDrilldown ?? [], 'scope', 'incoming') === 'all' ? 'btn-primary' : 'btn-light' }}">All</a>
            <a href="{{ route('dashboard', array_merge(request()->query(), ['transfer_kpi_window' => '7'])) }}" class="btn btn-sm {{ data_get($transferKpiDrilldown ?? [], 'window', '30') === '7' ? 'btn-secondary' : 'btn-light' }}">7d</a>
            <a href="{{ route('dashboard', array_merge(request()->query(), ['transfer_kpi_window' => '30'])) }}" class="btn btn-sm {{ data_get($transferKpiDrilldown ?? [], 'window', '30') === '30' ? 'btn-secondary' : 'btn-light' }}">30d</a>
            <a href="{{ route('dashboard', array_merge(request()->query(), ['transfer_kpi_window' => '90'])) }}" class="btn btn-sm {{ data_get($transferKpiDrilldown ?? [], 'window', '30') === '90' ? 'btn-secondary' : 'btn-light' }}">90d</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-2">
            <div class="col-md-3"><strong>Pending:</strong> {{ data_get($transferKpiDrilldown ?? [], 'status_counts.pending', 0) }}</div>
            <div class="col-md-3"><strong>Accepted:</strong> {{ data_get($transferKpiDrilldown ?? [], 'status_counts.accepted', 0) }}</div>
            <div class="col-md-3"><strong>Rejected:</strong> {{ data_get($transferKpiDrilldown ?? [], 'status_counts.rejected', 0) }}</div>
            <div class="col-md-3"><strong>Cancelled:</strong> {{ data_get($transferKpiDrilldown ?? [], 'status_counts.cancelled', 0) }}</div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-striped table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Pending</th>
                        <th>Accepted</th>
                        <th>Rejected</th>
                        <th>Cancelled</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(data_get($transferKpiDrilldown ?? [], 'trend', []) as $day => $row)
                    <tr>
                        <td>{{ $day }}</td>
                        <td>{{ $row['pending'] ?? 0 }}</td>
                        <td>{{ $row['accepted'] ?? 0 }}</td>
                        <td>{{ $row['rejected'] ?? 0 }}</td>
                        <td>{{ $row['cancelled'] ?? 0 }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No trend data for selected scope/window.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header header-elements-inline">
        <h6 class="card-title font-weight-semibold">Notification Reliability</h6>
        <span class="badge badge-warning">Pending failures: {{ data_get($transferNotificationVisibility ?? [], 'pending_failures_count', 0) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Transfer ID</th>
                        <th>Recipient</th>
                        <th>Notification</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(data_get($transferNotificationVisibility ?? [], 'recent_failures', collect()) as $failure)
                    <tr>
                        <td>{{ optional($failure->created_at)->toDateTimeString() }}</td>
                        <td>{{ $failure->transfer_id ?: 'N/A' }}</td>
                        <td>{{ $failure->notifiable_email ?: 'N/A' }}</td>
                        <td>{{ class_basename($failure->notification_class) }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($failure->error ?: 'N/A', 80) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No pending notification failures.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header header-elements-inline">
        <h6 class="card-title font-weight-semibold">Newly Received Students</h6>
        <div class="d-flex align-items-center" style="gap:8px;">
            <a href="{{ route('dashboard', ['received_window' => '7']) }}" class="btn btn-sm {{ ($receivedWindow ?? '7') === '7' ? 'btn-primary' : 'btn-light' }}">Last 7 days</a>
            <a href="{{ route('dashboard', ['received_window' => '30']) }}" class="btn btn-sm {{ ($receivedWindow ?? '7') === '30' ? 'btn-primary' : 'btn-light' }}">Last 30 days</a>
            <a href="{{ route('dashboard', ['received_window' => 'all']) }}" class="btn btn-sm {{ ($receivedWindow ?? '7') === 'all' ? 'btn-primary' : 'btn-light' }}">All</a>
            <span class="badge badge-success">{{ $recentlyReceivedTransfers->count() }}</span>
        </div>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">Use this list to confirm identity before/after admission updates.</p>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Previous School</th>
                        <th>Parent</th>
                        <th>Class</th>
                        <th>Transferred At</th>
                        <th style="width: 300px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentlyReceivedTransfers as $transfer)
                    @php
                    $receivedStudent = $transfer->student;
                    $studentRecord = optional($receivedStudent)->student_record;
                    $studentRecordId = optional($studentRecord)->id;
                    $verifyToken = $receivedTransferQrTokens[optional($receivedStudent)->id] ?? null;
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img class="rounded-circle mr-2" style="height: 40px; width: 40px; object-fit: cover;" src="{{ optional($receivedStudent)->photo }}" alt="photo">
                                <div>
                                    <div class="font-weight-semibold">{{ optional($receivedStudent)->name ?: 'N/A' }}</div>
                                    <small class="text-muted">{{ optional($receivedStudent)->code ?: 'N/A' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ optional($transfer->fromSchool)->name ?: 'N/A' }}</td>
                        <td>
                            {{ optional(optional($studentRecord)->my_parent)->name ?: 'N/A' }}
                            <br>
                            <small class="text-muted">{{ optional(optional($studentRecord)->my_parent)->phone ?: 'N/A' }}</small>
                        </td>
                        <td>
                            {{ optional(optional($studentRecord)->my_class)->name ?: 'N/A' }}
                            {{ optional(optional($studentRecord)->section)->name ?: '' }}
                        </td>
                        <td>{{ optional($transfer->transferred_at)->toDateTimeString() ?: optional($transfer->updated_at)->toDateTimeString() }}</td>
                        <td>
                            <a href="{{ route('transfers.show', $transfer) }}" class="btn btn-sm btn-primary mb-1">Transfer Details</a>
                            @if($studentRecordId)
                            <a href="{{ route('students.show', Qs::hash($studentRecordId)) }}" class="btn btn-sm btn-outline-primary mb-1">Profile</a>
                            @endif
                            @if(optional($receivedStudent)->id)
                            <a href="{{ route('students.transcript.show', $receivedStudent->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary mb-1">Transcript</a>
                            @endif
                            @if($verifyToken)
                            <a href="{{ route('students.verify.public', $verifyToken) }}" target="_blank" class="btn btn-sm btn-outline-success mb-1">Verify</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No received transfers for this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
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