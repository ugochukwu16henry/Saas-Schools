@extends('platform.layouts.master')

@section('page_title', 'Affiliates')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Affiliates</h1>
    <a href="{{ route('platform.affiliates.export', request()->query()) }}" class="btn btn-outline-primary">Export CSV</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('platform.affiliates.index') }}" class="row">
            <div class="col-md-5 mb-2">
                <input type="text" name="q" class="form-control" placeholder="Search name, email, phone, code" value="{{ request('q') }}">
            </div>
            <div class="col-md-3 mb-2">
                <select name="status" class="form-control">
                    <option value="">All statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <button class="btn btn-primary" type="submit">Filter</button>
                <a href="{{ route('platform.affiliates.index') }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-bordered mb-0">
                <thead>
                    <tr>
                        <th style="width:64px;">Photo</th>
                        <th>Affiliate</th>
                        <th>Status</th>
                        <th>Code</th>
                        <th class="text-center">Schools</th>
                        <th class="text-right">MTD ₦</th>
                        <th class="text-right">All-time ₦</th>
                        <th>Bank</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($affiliates as $a)
                        <tr>
                            <td>
                                @if ($a->photo_path)
                                    <img src="{{ asset('storage/'.$a->photo_path) }}" alt="" class="rounded" style="width:48px;height:48px;object-fit:cover;">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="font-weight-semibold">{{ $a->name }}</div>
                                <div class="small text-muted">{{ $a->email }}</div>
                                <div class="small text-muted">{{ $a->phone }}</div>
                            </td>
                            <td>
                                @if ($a->status === 'approved')
                                    <span class="badge badge-success">Approved</span>
                                @elseif ($a->status === 'pending')
                                    <span class="badge badge-warning">Pending</span>
                                @else
                                    <span class="badge badge-secondary">Suspended</span>
                                @endif
                            </td>
                            <td><code>{{ $a->code ?: '—' }}</code></td>
                            <td class="text-center">{{ number_format($a->schools_count) }}</td>
                            <td class="text-right">₦{{ number_format((float) ($a->mtd_commission_ngn ?? 0)) }}</td>
                            <td class="text-right">₦{{ number_format((float) ($a->total_commission_ngn ?? 0)) }}</td>
                            <td class="small">
                                {{ $a->bank_name ?: '—' }}<br>
                                {{ $a->account_name ?: '' }}
                            </td>
                            <td>
                                <a class="btn btn-sm btn-primary" href="{{ route('platform.affiliates.show', $a) }}">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted p-4">No affiliates found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $affiliates->links() }}
    </div>
</div>
@endsection
