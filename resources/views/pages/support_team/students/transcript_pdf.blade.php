<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Student Transcript</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
        }

        .header {
            margin-bottom: 16px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 8px 0;
        }

        .muted {
            color: #6b7280;
        }

        .grid {
            width: 100%;
            margin-bottom: 12px;
        }

        .grid td {
            vertical-align: top;
        }

        .photo {
            width: 110px;
            height: 110px;
            object-fit: cover;
            border: 1px solid #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        th,
        td {
            border: 1px solid #cfd4dc;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background: #f3f4f6;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin: 12px 0 6px;
        }

        .small {
            font-size: 11px;
        }
    </style>
</head>

<body>
    <div class="header">
        <p class="title">Official Student Transcript</p>
        <p class="small muted">Generated: {{ now()->toDateTimeString() }}</p>
    </div>

    <table class="grid">
        <tr>
            <td style="width:120px;">
                <img class="photo" src="{{ $student->photo }}" alt="Student photo">
            </td>
            <td>
                <p><strong>Name:</strong> {{ $student->name }}</p>
                <p><strong>Student Code:</strong> {{ $student->code }}</p>
                <p><strong>Admission Number:</strong> {{ optional($record)->adm_no ?: 'N/A' }}</p>
                <p><strong>Current School:</strong> {{ optional($student->school)->name ?: 'N/A' }}</p>
                <p><strong>School Email:</strong> {{ optional($student->school)->email ?: 'N/A' }}</p>
                <p><strong>School Phone:</strong> {{ optional($student->school)->phone ?: 'N/A' }}</p>
                <p><strong>Verification URL:</strong> {{ $verifyUrl }}</p>
            </td>
        </tr>
    </table>

    @if(optional($student->school)->logo)
    <p><strong>Current School Logo:</strong></p>
    <p><img src="{{ $student->school->logo }}" alt="Current school logo" style="height:40px; max-width:200px;"></p>
    @endif

    <div class="section-title">Transfer History</div>
    <table>
        <thead>
            <tr>
                <th>From School</th>
                <th>To School</th>
                <th>School Contacts</th>
                <th>Transfer Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transfers as $transfer)
            <tr>
                <td>
                    @if(optional($transfer->fromSchool)->logo)
                    <img src="{{ $transfer->fromSchool->logo }}" alt="From school logo" style="height:24px; max-width:90px;"><br>
                    @endif
                    {{ optional($transfer->fromSchool)->name ?: 'N/A' }}
                </td>
                <td>
                    @if(optional($transfer->toSchool)->logo)
                    <img src="{{ $transfer->toSchool->logo }}" alt="To school logo" style="height:24px; max-width:90px;"><br>
                    @endif
                    {{ optional($transfer->toSchool)->name ?: 'N/A' }}
                </td>
                <td>
                    From: {{ optional($transfer->fromSchool)->email ?: 'N/A' }} | {{ optional($transfer->fromSchool)->phone ?: 'N/A' }}<br>
                    To: {{ optional($transfer->toSchool)->email ?: 'N/A' }} | {{ optional($transfer->toSchool)->phone ?: 'N/A' }}
                </td>
                <td>{{ optional($transfer->transferred_at)->toDateTimeString() ?: optional($transfer->updated_at)->toDateTimeString() }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4">No transfer history found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Exam Summary</div>
    <table>
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
                <td colspan="7">No exam records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Subject Performance</div>
    <table>
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
                <td colspan="9">No subject marks found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Promotion History</div>
    <table>
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
                <td colspan="5">No promotion history found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>