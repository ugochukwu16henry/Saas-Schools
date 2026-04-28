@extends('platform.layouts.master')

@section('page_title', 'Affiliate: '.$affiliate->name)

@section('content')
<div class="mb-3">
    <a href="{{ route('platform.affiliates.index') }}" class="btn btn-light btn-sm">&larr; All affiliates</a>
</div>

<div class="row">
    <div class="col-lg-8 mb-3">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $affiliate->name }}</h5>
                @if ($affiliate->status === 'approved')
                <span class="badge badge-success">Approved</span>
                @elseif ($affiliate->status === 'pending')
                <span class="badge badge-warning">Pending</span>
                @else
                <span class="badge badge-secondary">Suspended</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Email:</strong> {{ $affiliate->email }}</p>
                        <p><strong>Phone:</strong> {{ $affiliate->phone }}</p>
                        <p><strong>Country:</strong> {{ $affiliate->country ?: '—' }}</p>
                        <p><strong>Referral code:</strong> <code>{{ $affiliate->code ?: '—' }}</code></p>
                        <p><strong>Registered:</strong> {{ $affiliate->created_at->format('d M Y H:i') }}</p>
                        @if ($affiliate->approved_at)
                        <p><strong>Approved:</strong> {{ $affiliate->approved_at->format('d M Y H:i') }}</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <p><strong>MTD commission (ledger):</strong> ₦{{ number_format((float) $mtdEarned) }}</p>
                        <p><strong>All-time commission (ledger):</strong> ₦{{ number_format((float) $totalEarned) }}</p>
                        <p><strong>Total paid out:</strong> ₦{{ number_format((float) $totalPaid) }}</p>
                        <p><strong>Pending payouts:</strong> ₦{{ number_format((float) $pendingPayouts) }}</p>
                        <p><strong>Available to payout:</strong> <span class="font-weight-semibold text-success">₦{{ number_format((float) $availableForPayout) }}</span></p>
                        <p><strong>Referred schools:</strong> {{ number_format($affiliate->schools_count) }}</p>
                        <p><strong>Default affiliate rates:</strong> Each school follows its assigned billing plan.</p>
                    </div>
                </div>
                @if ($affiliate->bio)
                <hr>
                <h6>Application note</h6>
                <p class="mb-0">{{ $affiliate->bio }}</p>
                @endif
                @if ($affiliate->admin_notes)
                <hr>
                <h6>Admin notes</h6>
                <p class="mb-0">{{ $affiliate->admin_notes }}</p>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Payout details</h5>
            </div>
            <div class="card-body">
                <p><strong>Bank:</strong> {{ $affiliate->bank_name ?: '—' }}</p>
                <p><strong>Account name:</strong> {{ $affiliate->account_name ?: '—' }}</p>
                <p><strong>Account number:</strong> {{ $affiliate->account_number ?: '—' }}</p>
                <hr>
                <p class="mb-1"><strong>Available balance:</strong> ₦{{ number_format((float) $availableForPayout) }}</p>
                <p class="text-muted mb-0 small">Only available balance can be requested for payout.</p>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Payout history</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th class="text-right">Amount</th>
                                <th>Status</th>
                                <th>Handled by</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payouts as $p)
                            <tr>
                                <td>{{ $p->created_at->format('M j, Y H:i') }}</td>
                                <td class="text-right">₦{{ number_format($p->amount_ngn) }}</td>
                                <td>
                                    @if($p->status === 'paid')
                                    <span class="badge badge-success">Paid</span>
                                    @else
                                    <span class="badge badge-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if($p->status === 'paid')
                                    {{ optional($p->paidBy)->name ?: '—' }}
                                    @if($p->paid_at)
                                    <div class="text-muted small">{{ $p->paid_at->format('d M Y H:i') }}</div>
                                    @endif
                                    @else
                                    {{ optional($p->approvedBy)->name ?: '—' }}
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted p-3">No payouts yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Referred schools</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>School</th>
                                <th class="text-center">Plan</th>
                                <th>Status</th>
                                <th class="text-center">Students</th>
                                <th class="text-center">Billable</th>
                                <th class="text-center">Rates</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($schools as $school)
                            <tr>
                                <td>{{ $school->name }}</td>
                                <td class="text-center">{{ optional($school->billingPlan)->name ?: 'Standard' }}</td>
                                <td>{{ $school->status }}</td>
                                <td class="text-center">{{ $school->students_count }}</td>
                                <td class="text-center">{{ $school->billable_count }}</td>
                                <td class="text-center">₦{{ number_format($school->effectiveAffiliateOneTimeCommissionRate()) }} / ₦{{ number_format($school->effectiveAffiliateMonthlyCommissionRate()) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted p-3">No schools yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Commission ledger</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>School</th>
                                <th>Paystack ref</th>
                                <th class="text-right">Total ₦</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ledger as $row)
                            <tr>
                                <td>{{ $row->created_at->format('M j, Y H:i') }}</td>
                                <td>{{ $row->school->name ?? '—' }}</td>
                                <td><small>{{ $row->paystack_reference }}</small></td>
                                <td class="text-right">{{ number_format($row->total_commission_ngn) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted p-3">No ledger rows.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">{{ $ledger->links() }}</div>
        </div>
    </div>

    <div class="col-lg-4 mb-3">
        @if ($affiliate->photo_path)
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Photo / KYC</h5>
            </div>
            <div class="card-body text-center">
                <a href="{{ asset('storage/'.$affiliate->photo_path) }}" target="_blank" rel="noopener">
                    <img src="{{ asset('storage/'.$affiliate->photo_path) }}" alt="Affiliate photo" class="img-fluid rounded">
                </a>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Actions</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('platform.affiliates.payouts.create', $affiliate) }}" class="mb-3">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-semibold">Create payout request (₦)</label>
                        <input type="number" name="amount_ngn" class="form-control" min="1" step="1" value="{{ old('amount_ngn') }}" required>
                        <small class="text-muted">Available: ₦{{ number_format((float) $availableForPayout) }}</small>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-semibold">Notes (optional)</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" {{ $availableForPayout <= 0 ? 'disabled' : '' }}>Create Pending Payout</button>
                </form>

                @php $pendingRows = $payouts->where('status', 'pending'); @endphp
                @if($pendingRows->count())
                <div class="mb-3">
                    <label class="font-weight-semibold d-block">Mark pending payouts as paid</label>
                    @foreach($pendingRows as $pending)
                    <form method="POST" action="{{ route('platform.affiliates.payouts.paid', [$affiliate, $pending]) }}" class="mb-2">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline-success btn-sm btn-block">
                            Mark ₦{{ number_format($pending->amount_ngn) }} ({{ $pending->created_at->format('d M Y') }}) as paid
                        </button>
                    </form>
                    @endforeach
                </div>
                @endif

                @if ($affiliate->status !== 'approved' || ! $affiliate->code)
                <form method="POST" action="{{ route('platform.affiliates.approve', $affiliate) }}" class="mb-3">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <label class="font-weight-semibold">Admin notes (optional)</label>
                        <textarea name="admin_notes" class="form-control" rows="2">{{ old('admin_notes') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">Approve</button>
                </form>
                @endif

                @if ($affiliate->status !== 'suspended')
                <form method="POST" action="{{ route('platform.affiliates.suspend', $affiliate) }}" onsubmit="return confirm('Suspend this affiliate?');">
                    @csrf
                    @method('PATCH')
                    <div class="form-group">
                        <label class="font-weight-semibold">Admin notes (optional)</label>
                        <textarea name="admin_notes" class="form-control" rows="2">{{ old('admin_notes') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-warning btn-block">Suspend</button>
                </form>
                @endif

                <hr>
                <form method="POST" action="{{ route('platform.affiliates.destroy', $affiliate) }}" onsubmit="return confirm('Delete this affiliate account? Referred schools will be retained.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-block">Delete Affiliate</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection