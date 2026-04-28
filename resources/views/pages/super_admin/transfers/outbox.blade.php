@extends('layouts.master')
@section('page_title', 'Outgoing Student Transfers')
@section('content')

<div class="card">
    <div class="card-header header-elements-inline">
        <h6 class="card-title font-weight-semibold">Outgoing Transfers</h6>
        <a href="{{ route('transfers.create') }}" class="btn btn-primary btn-sm">New Transfer</a>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>To School</th>
                        <th>Status</th>
                        <th>Accepted By</th>
                        <th>Transferred At</th>
                        <th style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $transfer)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="{{ optional($transfer->student)->photo }}" alt="Student photo" class="rounded-circle mr-2" style="height:40px;width:40px;object-fit:cover;">
                                <div>
                                    <div class="font-weight-semibold">{{ optional($transfer->student)->name }}</div>
                                    <small class="text-muted">{{ optional($transfer->student)->code }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ optional($transfer->toSchool)->name }}</td>
                        <td><span class="badge badge-secondary">{{ strtoupper($transfer->status) }}</span></td>
                        <td>{{ optional($transfer->acceptedBy)->name }}</td>
                        <td>{{ optional($transfer->transferred_at)->toDateTimeString() }}</td>
                        <td>
                            <a href="{{ route('transfers.show', $transfer) }}" class="btn btn-primary btn-sm mb-1">View Details</a>

                            @if($transfer->status === 'pending')
                            <form method="post" action="{{ route('transfers.cancel', $transfer) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                            </form>
                            @else
                            <span class="text-muted">No actions</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No outgoing transfers.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $transfers->links() }}
    </div>
</div>
@endsection