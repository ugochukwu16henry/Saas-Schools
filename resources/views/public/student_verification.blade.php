@extends('layouts.master')
@section('page_title', 'Student Verification')
@section('content')

<div class="card">
    <div class="card-header header-elements-inline">
        <h6 class="card-title font-weight-semibold">Student Verification</h6>
    </div>

    <div class="card-body">
        @if(!$verified)
        <div class="alert alert-danger alert-styled-left">
            <span class="font-weight-semibold">Verification Failed:</span>
            {{ $message ?? 'Invalid student QR token.' }}
        </div>
        @else
        <div class="alert alert-success alert-styled-left">
            <span class="font-weight-semibold">Verified:</span>
            This student record is valid.
        </div>

        <div class="row">
            <div class="col-md-3 mb-3">
                <img src="{{ $student->photo }}" alt="Student photo" class="img-fluid rounded border" style="max-height: 180px;">
            </div>
            <div class="col-md-9">
                <h5 class="mb-2">{{ $student->name }}</h5>
                <p class="mb-1"><strong>Student Code:</strong> {{ $student->code }}</p>
                <p class="mb-1"><strong>Admission No:</strong> {{ optional($record)->adm_no ?: 'N/A' }}</p>
                <p class="mb-1"><strong>Current School:</strong> {{ optional($school)->name ?: 'N/A' }}</p>
                <p class="mb-1"><strong>School Email:</strong> {{ optional($school)->email ?: 'N/A' }}</p>
                <p class="mb-1"><strong>School Phone:</strong> {{ optional($school)->phone ?: 'N/A' }}</p>
                <p class="mb-1"><strong>Class/Section:</strong>
                    {{ optional(optional($record)->my_class)->name ?: 'N/A' }}
                    {{ optional(optional($record)->section)->name ?: '' }}
                </p>
                <p class="mb-1"><strong>Graduated:</strong> {{ optional($record)->grad ? 'Yes' : 'No' }}</p>
                <p class="mb-0 text-muted small"><strong>Verification Token:</strong> {{ $token }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection