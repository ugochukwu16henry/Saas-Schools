@extends('layouts.master')

@section('page_title', 'Onboarding Checklist')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3" style="gap:10px;">
    <div>
        <h4 class="mb-1">Onboarding Checklist</h4>
        <div class="text-muted">Track first-time school setup and launch readiness.</div>
    </div>
    <div>
        @if($school->onboarding_completed_at)
        <span class="badge badge-success p-2">Completed {{ $school->onboarding_completed_at->format('d M Y h:i A') }}</span>
        @else
        <span class="badge badge-info p-2">In Progress</span>
        @endif
    </div>
</div>

@if($errors->has('onboarding'))
<div class="alert alert-warning">{{ $errors->first('onboarding') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="font-weight-semibold">Progress</div>
            <div>{{ $completedCount }}/{{ $totalCount }} complete</div>
        </div>
        @php
        $progressPct = $totalCount ? (int) (($completedCount / $totalCount) * 100) : 0;
        $progressClass = $progressPct >= 100 ? 'w-100' : ($progressPct >= 75 ? 'w-75' : ($progressPct >= 50 ? 'w-50' : ($progressPct >= 25 ? 'w-25' : '')));
        @endphp
        <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-success {{ $progressClass }}" role="progressbar" aria-valuenow="{{ $progressPct }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Step</th>
                    <th style="width:130px;">Status</th>
                    <th style="width:180px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($steps as $step)
                <tr>
                    <td>{{ $step['label'] }}</td>
                    <td>
                        @if($step['done'])
                        <span class="badge badge-success">Done</span>
                        @else
                        <span class="badge badge-warning">Pending</span>
                        @endif
                    </td>
                    <td>
                        @if(!$step['done'])
                        <a href="{{ $step['action_route'] }}" class="btn btn-sm btn-outline-primary">{{ $step['action_label'] }}</a>
                        @else
                        <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex" style="gap:8px;">
    <form method="POST" action="{{ route('onboarding.complete') }}">
        @csrf
        @method('PATCH')
        <button type="submit" class="btn btn-success" {{ $allDone ? '' : 'disabled' }}>Mark Onboarding Complete</button>
    </form>
    <a href="{{ route('dashboard') }}" class="btn btn-light">Back to Dashboard</a>
</div>
@endsection