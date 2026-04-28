@extends('layouts.master')
@section('page_title', 'Incoming Student Transfers')
@section('content')

<div class="card">
    <div class="card-header header-elements-inline">
        <h6 class="card-title font-weight-semibold">Incoming Transfers</h6>
        {!! Qs::getPanelOptions() !!}
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>From School</th>
                        <th>Status</th>
                        <th>Requested By</th>
                        <th>Created</th>
                        <th style="width: 290px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $transfer)
                    <tr>
                        <td>{{ optional($transfer->student)->name }}</td>
                        <td>{{ optional($transfer->fromSchool)->name }}</td>
                        <td><span class="badge badge-info">{{ strtoupper($transfer->status) }}</span></td>
                        <td>{{ optional($transfer->requestedBy)->name }}</td>
                        <td>{{ optional($transfer->created_at)->toDateTimeString() }}</td>
                        <td>
                            @if($transfer->status === 'pending')
                            <form method="post" action="{{ route('transfers.accept', $transfer) }}" style="display:inline-block;">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-success btn-sm">Accept</button>
                            </form>

                            <form method="post" action="{{ route('transfers.reject', $transfer) }}" style="display:inline-block;" class="ml-2">
                                @csrf @method('PATCH')
                                <input type="text" name="rejected_reason" placeholder="Reject reason" required class="form-control form-control-sm mb-1" style="min-width: 180px;">
                                <button type="submit" class="btn btn-warning btn-sm">Reject</button>
                            </form>
                            @else
                            <span class="text-muted">No actions</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No incoming transfers.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $transfers->links() }}
    </div>
</div>
@endsection