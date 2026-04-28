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
                        <th>Parent</th>
                        <th>Class</th>
                        <th>Status</th>
                        <th>Requested By</th>
                        <th>Created</th>
                        <th style="width: 340px;">Actions</th>
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
                        <td>{{ optional($transfer->fromSchool)->name }}</td>
                        <td>{{ optional(optional(optional($transfer->student)->student_record)->my_parent)->name ?: 'N/A' }}</td>
                        <td>
                            {{ optional(optional(optional($transfer->student)->student_record)->my_class)->name ?: 'N/A' }}
                            {{ optional(optional(optional($transfer->student)->student_record)->section)->name ?: '' }}
                        </td>
                        <td><span class="badge badge-info">{{ strtoupper($transfer->status) }}</span></td>
                        <td>{{ optional($transfer->requestedBy)->name }}</td>
                        <td>{{ optional($transfer->created_at)->toDateTimeString() }}</td>
                        <td>
                            <a href="{{ route('transfers.show', $transfer) }}" class="btn btn-primary btn-sm mb-1">View Details</a>

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
                        <td colspan="8" class="text-center text-muted">No incoming transfers.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $transfers->links() }}
    </div>
</div>
@endsection