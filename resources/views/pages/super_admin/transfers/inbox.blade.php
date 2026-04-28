@extends('layouts.master')
@section('page_title', 'Incoming Student Transfers')
@section('content')

<div class="card">
    <div class="card-header header-elements-inline">
        <h6 class="card-title font-weight-semibold">Incoming Transfers</h6>
        <a href="{{ route('transfers.audit.export', ['scope' => 'inbox']) }}" class="btn btn-sm btn-outline-primary">Export CSV</a>
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
                    @php
                    $previewId = 'incoming-preview-' . $transfer->id;
                    @endphp
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
                            <button type="button" class="btn btn-light btn-sm mb-1" data-toggle="collapse" data-target="#{{ $previewId }}" aria-expanded="false" aria-controls="{{ $previewId }}">Quick Preview</button>
                            <a href="{{ route('transfers.show', $transfer) }}" class="btn btn-primary btn-sm mb-1">View Details</a>

                            @if($transfer->status === 'pending')
                            @if(config('transfers.policies.require_acceptance_checklist', true))
                            <a href="{{ route('transfers.show', $transfer) }}" class="btn btn-success btn-sm">Open Details to Accept</a>
                            @else
                            <form method="post" action="{{ route('transfers.accept', $transfer) }}" style="display:inline-block;">
                                @csrf @method('PATCH')
                                <input type="hidden" name="acceptance_checklist" value="1">
                                <button type="submit" class="btn btn-success btn-sm">Accept</button>
                            </form>
                            @endif

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
                    <tr class="collapse" id="{{ $previewId }}">
                        <td colspan="8" class="bg-light">
                            <div class="row py-2">
                                <div class="col-md-4 mb-2">
                                    <strong>Student:</strong> {{ optional($transfer->student)->name ?: 'N/A' }}<br>
                                    <strong>Code:</strong> {{ optional($transfer->student)->code ?: 'N/A' }}<br>
                                    <strong>Photo:</strong><br>
                                    <img src="{{ optional($transfer->student)->photo }}" alt="Student photo" class="rounded border mt-1" style="height:60px;width:60px;object-fit:cover;">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <strong>Parent:</strong> {{ optional(optional(optional($transfer->student)->student_record)->my_parent)->name ?: 'N/A' }}<br>
                                    <strong>Parent Phone:</strong> {{ optional(optional(optional($transfer->student)->student_record)->my_parent)->phone ?: 'N/A' }}<br>
                                    <strong>Class:</strong>
                                    {{ optional(optional(optional($transfer->student)->student_record)->my_class)->name ?: 'N/A' }}
                                    {{ optional(optional(optional($transfer->student)->student_record)->section)->name ?: '' }}
                                </div>
                                <div class="col-md-4 mb-2">
                                    <strong>From School:</strong> {{ optional($transfer->fromSchool)->name ?: 'N/A' }}<br>
                                    <strong>Requested By:</strong> {{ optional($transfer->requestedBy)->name ?: 'N/A' }}<br>
                                    <strong>Note:</strong> {{ $transfer->transfer_note ?: 'N/A' }}
                                </div>
                            </div>
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