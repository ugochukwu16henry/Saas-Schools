@extends('layouts.master')
@section('page_title', 'My Children')
@section('content')

<div class="card">
    <div class="card-header header-elements-inline">
        <h6 class="card-title">My Children</h6>
        {!! Qs::getPanelOptions() !!}
    </div>

    <div class="card-body">
        <table class="table datatable-button-html5-columns">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>ADM_No</th>
                    <th>Section</th>
                    <th>Previous School</th>
                    <th>Current School</th>
                    <th>Email</th>
                    <th>Verify</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $s)
                @php
                $history = ($transferHistoryByStudent[$s->user->id] ?? collect());
                $latest = $history->first();
                $previousSchool = optional(optional($latest)->fromSchool)->name;
                $currentSchool = optional(optional($latest)->toSchool)->name ?: optional($s->user->school)->name;
                $qrToken = $qrTokensByStudent[$s->user->id] ?? null;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><img class="rounded-circle" style="height: 40px; width: 40px;" src="{{ $s->user->photo }}" alt="photo"></td>
                    <td>{{ $s->user->name }}</td>
                    <td>{{ $s->adm_no }}</td>
                    <td>{{ $s->my_class->name.' '.$s->section->name }}</td>
                    <td>{{ $previousSchool ?: 'N/A' }}</td>
                    <td>{{ $currentSchool ?: 'N/A' }}</td>
                    <td>{{ $s->user->email }}</td>
                    <td>
                        @if($qrToken)
                        <a target="_blank" href="{{ route('students.verify.public', $qrToken) }}" class="btn btn-sm btn-outline-primary">Verify</a>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary ml-1 js-show-student-qr"
                            data-student-name="{{ $s->user->name }}"
                            data-verify-url="{{ route('students.verify.public', $qrToken) }}"
                        >
                            QR
                        </button>
                        @else
                        <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="list-icons">
                            <div class="dropdown">
                                <a href="#" class="list-icons-item" data-toggle="dropdown">
                                    <i class="icon-menu9"></i>
                                </a>

                                <div class="dropdown-menu dropdown-menu-left">
                                    <a href="{{ route('students.show', Qs::hash($s->id)) }}" class="dropdown-item"><i class="icon-eye"></i> View Profile</a>
                                    <a target="_blank" href="{{ route('marks.year_selector', Qs::hash($s->user->id)) }}" class="dropdown-item"><i class="icon-check"></i> Marksheet</a>
                                    <a target="_blank" href="{{ route('students.transcript.show', $s->user->id) }}" class="dropdown-item"><i class="icon-file-text2"></i> View Transcript</a>
                                    <a target="_blank" href="{{ route('students.transcript.download', $s->user->id) }}" class="dropdown-item"><i class="icon-download"></i> Download Transcript</a>

                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</div>

{{--Student List Ends--}}

<div class="modal fade" id="studentQrModal" tabindex="-1" role="dialog" aria-labelledby="studentQrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="studentQrModalLabel">Student Verification QR</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div id="parent-student-qrcode" class="d-inline-block mb-2"></div>
                <div class="small text-muted mb-1" id="parent-student-qr-name"></div>
                <a id="parent-student-qr-link" href="#" target="_blank" class="small"></a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('global_assets/js/plugins/qrcodejs/qrcode.min.js') }}?v=20260429"></script>
<script>
    (function() {
        var qrButtons = Array.prototype.slice.call(document.querySelectorAll('.js-show-student-qr'));
        if (!qrButtons.length || typeof QRCode === 'undefined') {
            return;
        }

        var qrContainer = document.getElementById('parent-student-qrcode');
        var qrName = document.getElementById('parent-student-qr-name');
        var qrLink = document.getElementById('parent-student-qr-link');
        var qrModal = $('#studentQrModal');

        qrButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var verifyUrl = btn.getAttribute('data-verify-url') || '';
                var studentName = btn.getAttribute('data-student-name') || 'Student';

                if (!verifyUrl || !qrContainer) {
                    return;
                }

                qrContainer.innerHTML = '';
                new QRCode(qrContainer, {
                    text: verifyUrl,
                    width: 160,
                    height: 160,
                    colorDark: '#111111',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });

                if (qrName) {
                    qrName.textContent = studentName;
                }
                if (qrLink) {
                    qrLink.textContent = verifyUrl;
                    qrLink.setAttribute('href', verifyUrl);
                }

                qrModal.modal('show');
            });
        });
    })();
</script>
@endsection