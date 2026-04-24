@extends('layouts.login_master')

@section('content')
<div class="page-content">
    <div class="content-wrapper">
        <div class="content py-4">
            <div class="container" style="max-width:1100px;">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <div>
                        <h1 class="h3 mb-1">Affiliate dashboard</h1>
                        <p class="text-muted mb-0">Welcome, {{ $affiliate->name }}.</p>
                    </div>
                    <div>
                        <form method="POST" action="{{ route('affiliate.logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-light">Log out</button>
                        </form>
                        <a href="{{ route('affiliate.profile.edit') }}" class="btn saas-btn-primary">Profile &amp; payout details</a>
                    </div>
                </div>

                @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Your referral link</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><span class="badge badge-success">Code {{ $affiliate->code }}</span></p>
                        <div class="input-group">
                            <input type="text" class="form-control" id="referral-url" readonly value="{{ $referralUrl }}">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('referral-url').value)">Copy</button>
                            </div>
                        </div>
                        @if(!empty($affiliate->code))
                        <p class="small text-muted mt-2 mb-0">Short link: <a href="{{ route('affiliate.referral_redirect', ['code' => $affiliate->code]) }}">{{ url('/r/'.$affiliate->code) }}</a> (redirects to school registration with your code).</p>
                        @else
                        <p class="small text-muted mt-2 mb-0">Short link will appear after your referral code is generated.</p>
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 mb-2">
                        <div class="card card-body border-left-success">
                            <div class="text-muted small">All-time recorded (from successful school payments)</div>
                            <div class="h4 mb-0">₦{{ number_format((float) $totalEarned) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="card card-body">
                            <div class="text-muted small">Month to date (ledger)</div>
                            <div class="h4 mb-0">₦{{ number_format((float) $mtdEarned) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="card card-body" title="Uses each school’s live billable student count × the monthly affiliate rate. Actual payouts follow successful Paystack charges recorded in your ledger.">
                            <div class="text-muted small">Live monthly projection</div>
                            <div class="h4 mb-0">₦{{ number_format((float) $liveMonthlyProjection) }}</div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Referred schools</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>School</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Students</th>
                                        <th class="text-center">Billable now</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($schools as $school)
                                    <tr>
                                        <td>{{ $school->name }}</td>
                                        <td class="text-center">{{ $school->status }}</td>
                                        <td class="text-center">{{ number_format($school->students_count) }}</td>
                                        <td class="text-center">{{ number_format($school->billable_count) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted p-4">No referred schools yet. Share your link with school owners.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent commission events</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>When</th>
                                        <th>School</th>
                                        <th class="text-right">One-time ₦</th>
                                        <th class="text-right">Monthly ₦</th>
                                        <th class="text-right">Total ₦</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentLedger as $row)
                                    <tr>
                                        <td>{{ $row->created_at->format('M j, H:i') }}</td>
                                        <td>{{ $row->school->name ?? '—' }}</td>
                                        <td class="text-right">{{ number_format($row->one_time_commission_ngn) }}</td>
                                        <td class="text-right">{{ number_format($row->monthly_commission_ngn) }}</td>
                                        <td class="text-right font-weight-semibold">{{ number_format($row->total_commission_ngn) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted p-4">No commission rows yet. They appear after referred schools complete Paystack payments.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection