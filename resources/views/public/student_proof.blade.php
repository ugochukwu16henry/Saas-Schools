<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Proof Verification</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f6fb;
            color: #1f2937;
        }
        .wrap {
            max-width: 760px;
            margin: 24px auto;
            padding: 0 16px;
        }
        .card {
            background: #ffffff;
            border: 1px solid #dbe3f0;
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(30, 41, 59, 0.06);
            overflow: hidden;
        }
        .header {
            padding: 14px 18px;
            background: #0f355f;
            color: #ffffff;
            font-weight: 700;
        }
        .body {
            padding: 18px;
        }
        .status-ok {
            background: #e8f8ef;
            border: 1px solid #b6e3c9;
            color: #15603f;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-weight: 600;
        }
        .status-bad {
            background: #fdebec;
            border: 1px solid #f2b6bc;
            color: #8b1f2b;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-weight: 600;
        }
        .grid {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 16px;
            align-items: start;
        }
        .photo, .logo {
            width: 100%;
            max-width: 160px;
            border-radius: 10px;
            border: 1px solid #dbe3f0;
            object-fit: cover;
            background: #fff;
        }
        .logo {
            object-fit: contain;
            padding: 6px;
            margin-top: 8px;
            max-height: 70px;
        }
        .line { margin-bottom: 8px; }
        .label { font-weight: 700; }
        .muted { color: #5b677a; font-size: 13px; }
        @media (max-width: 640px) {
            .grid { grid-template-columns: 1fr; }
            .photo, .logo { max-width: 120px; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="header">Student Identity Proof</div>
            <div class="body">
                @if(empty($verified))
                    <div class="status-bad">
                        Verification Failed: {{ $message ?? 'Student record could not be verified.' }}
                    </div>
                    <div class="muted">Token: {{ $token ?? 'N/A' }}</div>
                @else
                    <div class="status-ok">Verified Student Record</div>
                    @php
                        $schoolLogo = trim((string) (optional($school)->logo ?? ''));
                        if ($schoolLogo === '') {
                            $schoolLogo = asset('global_assets/images/riseflow-logo.png');
                        }
                    @endphp
                    <div class="grid">
                        <div>
                            <img class="photo" src="{{ $student->photo }}" alt="Student Photo">
                            <img class="logo" src="{{ $schoolLogo }}" alt="School Logo">
                        </div>
                        <div>
                            <div class="line"><span class="label">Student Full Name:</span> {{ $student->name ?: 'N/A' }}</div>
                            <div class="line"><span class="label">Admission Number:</span> {{ optional($record)->adm_no ?: 'N/A' }}</div>
                            <div class="line"><span class="label">School Name:</span> {{ optional($school)->name ?: 'N/A' }}</div>
                            <div class="line"><span class="label">School Email:</span> {{ optional($school)->email ?: 'N/A' }}</div>
                            <div class="line"><span class="label">School Phone:</span> {{ optional($school)->phone ?: 'N/A' }}</div>
                            <div class="line"><span class="label">School Address:</span> {{ optional($school)->address ?: 'N/A' }}</div>
                            <div class="muted">Verification Token: {{ $token }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
