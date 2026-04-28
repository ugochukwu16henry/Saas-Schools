@extends('layouts.master')
@section('page_title', 'Student Transcript')
@section('content')

<div class="card mb-3">
    <div class="card-header header-elements-inline">
        <h6 class="card-title font-weight-semibold">Student Transcript</h6>
        <div>
            <a href="{{ route('students.transcript.download', $student->id) }}" class="btn btn-primary btn-sm">Download PDF Transcript</a>
        </div>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-2 mb-2">
                <img src="{{ $student->photo }}" alt="Student photo" class="img-fluid rounded border" style="max-height: 140px;">
            </div>
            <div class="col-md-10">
                <h5 class="mb-2">{{ $student->name }}</h5>
                <p class="mb-1"><strong>Student Code:</strong> {{ $student->code }}</p>
                <p class="mb-1"><strong>Admission Number:</strong> {{ optional($record)->adm_no ?: 'N/A' }}</p>
                <p class="mb-1"><strong>Current School:</strong> {{ optional($student->school)->name ?: 'N/A' }}</p>
                <p class="mb-0"><strong>Verification URL:</strong> <a href="{{ $verifyUrl }}" target="_blank">{{ $verifyUrl }}</a></p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h6 class="card-title mb-0">Transfer History</h6>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>From School</th>
                    <th>To School</th>
                    <th>Transfer Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transfers as $transfer)
                <tr>
                    <td>{{ optional($transfer->fromSchool)->name ?: 'N/A' }}</td>
                    <td>{{ optional($transfer->toSchool)->name ?: 'N/A' }}</td>
                    <td>{{ optional($transfer->transferred_at)->toDateTimeString() ?: optional($transfer->updated_at)->toDateTimeString() }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center text-muted">No transfer history found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h6 class="card-title mb-0">Exam Summary</h6>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Exam</th>
                    <th>Class</th>
                    <th>Section</th>
                    <th>Total</th>
                    <th>Average</th>
                    <th>Position</th>
                </tr>
            </thead>
            <tbody>
                @forelse($examRecords as $row)
                <tr>
                    <td>{{ $row->year }}</td>
                    <td>{{ optional($row->exam)->name ?: 'N/A' }}</td>
                    <td>{{ optional($row->my_class)->name ?: 'N/A' }}</td>
                    <td>{{ optional($row->section)->name ?: 'N/A' }}</td>
                    <td>{{ $row->total ?? 'N/A' }}</td>
                    <td>{{ $row->ave ?? 'N/A' }}</td>
                    <td>{{ $row->pos ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">No exam records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h6 class="card-title mb-0">Subject Performance</h6>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Exam</th>
                    <th>Subject</th>
                    <th>Class</th>
                    <th>Section</th>
                    <th>CA</th>
                    <th>Exam Score</th>
                    <th>Cumulative</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                @forelse($marks as $mark)
                <tr>
                    <td>{{ $mark->year }}</td>
                    <td>{{ optional($mark->exam)->name ?: 'N/A' }}</td>
                    <td>{{ optional($mark->subject)->name ?: 'N/A' }}</td>
                    <td>{{ optional($mark->my_class)->name ?: 'N/A' }}</td>
                    <td>{{ optional($mark->section)->name ?: 'N/A' }}</td>
                    <td>{{ $mark->tca ?? 'N/A' }}</td>
                    <td>{{ $mark->exm ?? 'N/A' }}</td>
                    <td>{{ $mark->cum ?? 'N/A' }}</td>
                    <td>{{ optional($mark->grade)->name ?: 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted">No subject marks found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">Promotion History</h6>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>From Session</th>
                    <th>From Class/Section</th>
                    <th>To Session</th>
                    <th>To Class/Section</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($promotions as $promotion)
                <tr>
                    <td>{{ $promotion->from_session ?: 'N/A' }}</td>
                    <td>{{ optional($promotion->fc)->name ?: 'N/A' }} {{ optional($promotion->fs)->name ?: '' }}</td>
                    <td>{{ $promotion->to_session ?: 'N/A' }}</td>
                    <td>{{ optional($promotion->tc)->name ?: 'N/A' }} {{ optional($promotion->ts)->name ?: '' }}</td>
                    <td>{{ strtoupper((string) $promotion->status) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">No promotion history found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection