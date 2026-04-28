@extends('layouts.master')
@section('page_title', 'Transfer Details')
@section('content')

<div class="card">
    <div class="card-header header-elements-inline">
        <h6 class="card-title font-weight-semibold">Transfer Details</h6>
        <div>
            <a href="{{ route('transfers.inbox') }}" class="btn btn-light btn-sm">Incoming</a>
            <a href="{{ route('transfers.outbox') }}" class="btn btn-light btn-sm">Outgoing</a>
        </div>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <img src="{{ optional($student)->photo }}" alt="Student photo" class="img-fluid rounded border" style="max-height: 220px; object-fit: cover;">
            </div>
            <div class="col-md-9 mb-3">
                <h5 class="mb-2">{{ optional($student)->name }}</h5>
                <p class="mb-1"><strong>Student Code:</strong> {{ optional($student)->code ?: 'N/A' }}</p>
                <p class="mb-1"><strong>Admission Number:</strong> {{ optional($studentRecord)->adm_no ?: 'N/A' }}</p>
                <p class="mb-1"><strong>Email:</strong> {{ optional($student)->email ?: 'N/A' }}</p>
                <p class="mb-1"><strong>Phone:</strong> {{ optional($student)->phone ?: 'N/A' }}</p>
                <p class="mb-1"><strong>Gender:</strong> {{ optional($student)->gender ?: 'N/A' }}</p>
                <p class="mb-1"><strong>Date of Birth:</strong> {{ optional($student)->dob ?: 'N/A' }}</p>
                <p class="mb-1"><strong>Address:</strong> {{ optional($student)->address ?: 'N/A' }}</p>
                <p class="mb-1"><strong>Status:</strong> <span class="badge badge-info">{{ strtoupper(optional($transfer)->status) }}</span></p>
                <p class="mb-1"><strong>Class:</strong> {{ optional(optional($studentRecord)->my_class)->name ?: 'N/A' }} {{ optional(optional($studentRecord)->section)->name ?: '' }}</p>
                <p class="mb-0"><strong>Session:</strong> {{ optional($studentRecord)->session ?: optional($transfer)->from_session ?: 'N/A' }}</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="border rounded p-3 h-100">
                    <h6 class="font-weight-semibold">Parent / Guardian</h6>
                    <p class="mb-1"><strong>Name:</strong> {{ optional($parent)->name ?: optional(optional($studentRecord)->my_parent)->name ?: 'N/A' }}</p>
                    <p class="mb-1"><strong>Phone:</strong> {{ optional($parent)->phone ?: optional(optional($studentRecord)->my_parent)->phone ?: 'N/A' }}</p>
                    <p class="mb-1"><strong>Phone 2:</strong> {{ optional($parent)->phone2 ?: optional(optional($studentRecord)->my_parent)->phone2 ?: 'N/A' }}</p>
                    <p class="mb-0"><strong>Email:</strong> {{ optional($parent)->email ?: optional(optional($studentRecord)->my_parent)->email ?: 'N/A' }}</p>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="border rounded p-3 h-100">
                    <h6 class="font-weight-semibold">Transfer Timeline</h6>
                    <p class="mb-1"><strong>From School:</strong> {{ optional(optional($transfer)->fromSchool)->name ?: 'N/A' }}</p>
                    <p class="mb-1"><strong>From School Contact:</strong> {{ optional(optional($transfer)->fromSchool)->email ?: 'N/A' }} | {{ optional(optional($transfer)->fromSchool)->phone ?: 'N/A' }}</p>
                    <p class="mb-1"><strong>To School:</strong> {{ optional(optional($transfer)->toSchool)->name ?: 'N/A' }}</p>
                    <p class="mb-1"><strong>To School Contact:</strong> {{ optional(optional($transfer)->toSchool)->email ?: 'N/A' }} | {{ optional(optional($transfer)->toSchool)->phone ?: 'N/A' }}</p>
                    <p class="mb-1"><strong>Requested By:</strong> {{ optional(optional($transfer)->requestedBy)->name ?: 'N/A' }}</p>
                    <p class="mb-1"><strong>Requested At:</strong> {{ optional(optional($transfer)->created_at)->toDateTimeString() ?: 'N/A' }}</p>
                    <p class="mb-1"><strong>Transferred At:</strong> {{ optional(optional($transfer)->transferred_at)->toDateTimeString() ?: 'N/A' }}</p>
                    <p class="mb-0"><strong>Transfer Note:</strong> {{ optional($transfer)->transfer_note ?: 'N/A' }}</p>
                </div>
            </div>
        </div>

        @php
        $snapshotStudent = $transferSnapshot['student'] ?? [];
        $snapshotParent = $transferSnapshot['parent'] ?? [];
        $snapshotAcademic = $transferSnapshot['academic'] ?? [];
        @endphp

        @if(!empty($transferSnapshot))
        <div class="row">
            <div class="col-md-12 mb-3">
                <div class="border rounded p-3">
                    <h6 class="font-weight-semibold mb-2">Captured Snapshot (At Request Time)</h6>
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <strong>Student:</strong> {{ $snapshotStudent['name'] ?? 'N/A' }}<br>
                            <strong>Email:</strong> {{ $snapshotStudent['email'] ?? 'N/A' }}<br>
                            <strong>Phone:</strong> {{ $snapshotStudent['phone'] ?? 'N/A' }}
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Parent:</strong> {{ $snapshotParent['name'] ?? 'N/A' }}<br>
                            <strong>Parent Phone:</strong> {{ $snapshotParent['phone'] ?? 'N/A' }}<br>
                            <strong>Parent Email:</strong> {{ $snapshotParent['email'] ?? 'N/A' }}
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Class:</strong> {{ $snapshotAcademic['class_name'] ?? 'N/A' }} {{ $snapshotAcademic['section_name'] ?? '' }}<br>
                            <strong>Session:</strong> {{ $snapshotAcademic['session'] ?? 'N/A' }}<br>
                            <strong>Captured At:</strong> {{ $transferSnapshot['captured_at'] ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="row">
            <div class="col-md-12 mb-3">
                <div class="border rounded p-3">
                    <h6 class="font-weight-semibold mb-2">Transfer Audit Timeline</h6>
                    <ul class="mb-0 pl-3">
                        @forelse(($statusHistory ?? []) as $event)
                        <li class="mb-1">
                            <strong>{{ ucfirst($event['event'] ?? 'event') }}</strong>
                            @if(!empty($event['actor_name']))
                            by {{ $event['actor_name'] }}
                            @elseif(!empty($event['actor_id']))
                            by user #{{ $event['actor_id'] }}
                            @endif
                            @if(!empty($event['status']))
                            ({{ strtoupper($event['status']) }})
                            @endif
                            on {{ $event['at'] ?? 'N/A' }}
                            @if(!empty($event['reason']))
                            with reason: {{ $event['reason'] }}
                            @endif
                        </li>
                        @empty
                        <li class="text-muted">No audit history available.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-12">
                <div class="border rounded p-3">
                    <h6 class="font-weight-semibold">Academic Summary</h6>
                    <div class="row">
                        <div class="col-sm-4 mb-2"><strong>Exam Reports:</strong> {{ $examRecordsCount ?? 0 }}</div>
                        <div class="col-sm-4 mb-2"><strong>Marks:</strong> {{ $marksCount ?? 0 }}</div>
                        <div class="col-sm-4 mb-2"><strong>Promotions:</strong> {{ $promotionsCount ?? 0 }}</div>
                    </div>
                    <a href="{{ $transcriptUrl }}" class="btn btn-outline-primary btn-sm" target="_blank">View Full Transcript</a>
                    <a href="{{ $transcriptDownloadUrl }}" class="btn btn-outline-secondary btn-sm" target="_blank">Download Transcript PDF</a>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Recent Exam Reports</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Class / Section</th>
                                <th>Total</th>
                                <th>Average</th>
                                <th>Position</th>
                                <th>Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($recentExamRecords ?? collect()) as $examRecord)
                            <tr>
                                <td>{{ optional($examRecord->exam)->name ?: 'N/A' }}</td>
                                <td>{{ optional($examRecord->my_class)->name ?: 'N/A' }} {{ optional($examRecord->section)->name ?: '' }}</td>
                                <td>{{ $examRecord->total ?? 'N/A' }}</td>
                                <td>{{ $examRecord->ave ?? 'N/A' }}</td>
                                <td>{{ $examRecord->pos ?? 'N/A' }}</td>
                                <td>{{ $examRecord->year ?? 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No exam reports found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Recent Marks</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Exam</th>
                                <th>Total CA</th>
                                <th>Exam Score</th>
                                <th>Grade</th>
                                <th>Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($recentMarks ?? collect()) as $mark)
                            <tr>
                                <td>{{ optional($mark->subject)->name ?: 'N/A' }}</td>
                                <td>{{ optional($mark->exam)->name ?: 'N/A' }}</td>
                                <td>{{ $mark->tca ?? 'N/A' }}</td>
                                <td>{{ $mark->exm ?? 'N/A' }}</td>
                                <td>{{ optional($mark->grade)->name ?: 'N/A' }}</td>
                                <td>{{ $mark->year ?? 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No marks found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @php
        $isReceivingSchool = (int) optional($school)->id === (int) optional($transfer)->to_school_id;
        $isSendingSchool = (int) optional($school)->id === (int) optional($transfer)->from_school_id;
        @endphp

        @if(optional($transfer)->status === 'pending' && $isReceivingSchool)
        <div class="alert alert-warning border mb-3">
            <h6 class="font-weight-semibold mb-2">Verification Checklist (Required Before Accept)</h6>
            <div class="form-check mb-1">
                <input class="form-check-input transfer-verify-check" type="checkbox" id="verify-photo">
                <label class="form-check-label" for="verify-photo">I confirmed student photo and full name match the profile.</label>
            </div>
            <div class="form-check mb-1">
                <input class="form-check-input transfer-verify-check" type="checkbox" id="verify-parent">
                <label class="form-check-label" for="verify-parent">I reviewed parent/guardian names and phone numbers.</label>
            </div>
            <div class="form-check mb-1">
                <input class="form-check-input transfer-verify-check" type="checkbox" id="verify-academic">
                <label class="form-check-label" for="verify-academic">I reviewed class/session and academic reports.</label>
            </div>
            <small class="text-muted">Accept button stays disabled until all checks are completed.</small>
        </div>
        @endif

        <div class="d-flex flex-wrap align-items-start">
            @if(optional($transfer)->status === 'pending' && $isReceivingSchool)
            <form method="post" action="{{ route('transfers.accept', $transfer) }}" class="mr-2 mb-2">
                @csrf @method('PATCH')
                <button type="submit" id="accept-transfer-btn" class="btn btn-success btn-sm" disabled>Accept Transfer</button>
            </form>

            <form method="post" action="{{ route('transfers.reject', $transfer) }}" class="mr-2 mb-2">
                @csrf @method('PATCH')
                <input type="text" name="rejected_reason" placeholder="Reject reason" required class="form-control form-control-sm mb-1" style="min-width: 220px;">
                <button type="submit" class="btn btn-warning btn-sm">Reject Transfer</button>
            </form>
            @endif

            @if(optional($transfer)->status === 'pending' && $isSendingSchool)
            <form method="post" action="{{ route('transfers.cancel', $transfer) }}" class="mb-2">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Cancel Transfer</button>
            </form>
            @endif
        </div>
    </div>
</div>

@if(optional($transfer)->status === 'pending' && $isReceivingSchool)
<script>
    (function() {
        var checks = Array.prototype.slice.call(document.querySelectorAll('.transfer-verify-check'));
        var acceptBtn = document.getElementById('accept-transfer-btn');

        if (!checks.length || !acceptBtn) {
            return;
        }

        function syncAcceptState() {
            var allChecked = checks.every(function(check) {
                return check.checked;
            });
            acceptBtn.disabled = !allChecked;
        }

        checks.forEach(function(check) {
            check.addEventListener('change', syncAcceptState);
        });

        syncAcceptState();
    })();
</script>
@endif
@endsection