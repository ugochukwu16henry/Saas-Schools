@extends('platform.layouts.master')

@section('page_title', 'Usage Analytics')

@section('content')
@php
$scopeLabel = $selectedSchool ? $selectedSchool->name : 'All Schools';
$maxStudent = max($trends['students_cumulative'] ?: [0]);
$maxTeacher = max($trends['teachers_cumulative'] ?: [0]);
$maxExam = max($trends['exams_monthly'] ?: [0]);
$maxClass = max($trends['classes_monthly'] ?: [0]);
$maxSubject = max($trends['subjects_monthly'] ?: [0]);
@endphp

<div class="card">
    <div class="card-header header-elements-inline">
        <h5 class="card-title mb-0">Usage Analytics</h5>
        <span class="text-muted small">Scope: {{ $scopeLabel }}</span>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('platform.usage.index') }}" class="mb-3">
            <div class="row">
                <div class="col-md-5 mb-2">
                    <label class="small text-muted mb-1">School</label>
                    <select name="school_id" class="form-control">
                        <option value="">All Schools</option>
                        @foreach($schools as $school)
                        <option value="{{ $school->id }}" {{ (string) $selectedSchoolId === (string) $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small text-muted mb-1">Window</label>
                    <select name="months" class="form-control">
                        <option value="6" {{ $months === 6 ? 'selected' : '' }}>Last 6 months</option>
                        <option value="12" {{ $months === 12 ? 'selected' : '' }}>Last 12 months</option>
                        <option value="18" {{ $months === 18 ? 'selected' : '' }}>Last 18 months</option>
                        <option value="24" {{ $months === 24 ? 'selected' : '' }}>Last 24 months</option>
                    </select>
                </div>
                <div class="col-md-4 mb-2 d-flex align-items-end">
                    <button class="btn btn-primary mr-2" type="submit">Apply</button>
                    <a href="{{ route('platform.usage.index') }}" class="btn btn-light">Reset</a>
                </div>
            </div>
        </form>

        <div class="row mb-3">
            <div class="col-sm-6 col-xl-3 mb-2">
                <div class="card card-body bg-primary text-white mb-0">
                    <h3 class="font-weight-semibold mb-0">{{ number_format($totals['students']) }}</h3>
                    <div>Total Students</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 mb-2">
                <div class="card card-body bg-info text-white mb-0">
                    <h3 class="font-weight-semibold mb-0">{{ number_format($totals['teachers']) }}</h3>
                    <div>Total Teachers</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 mb-2">
                <div class="card card-body bg-success text-white mb-0">
                    <h3 class="font-weight-semibold mb-0">{{ number_format($totals['exams']) }}</h3>
                    <div>Total Exams</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 mb-2">
                <div class="card card-body bg-indigo text-white mb-0">
                    <h3 class="font-weight-semibold mb-0">{{ number_format($totals['new_users_30d']) }}</h3>
                    <div>New Users (30d)</div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-sm-6 col-xl-3 mb-2">
                <div class="card card-body mb-0 bg-light">
                    <div class="text-muted small">Classes</div>
                    <h4 class="mb-0">{{ number_format($totals['classes']) }}</h4>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 mb-2">
                <div class="card card-body mb-0 bg-light">
                    <div class="text-muted small">Subjects</div>
                    <h4 class="mb-0">{{ number_format($totals['subjects']) }}</h4>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 mb-2">
                <div class="card card-body mb-0 bg-light">
                    <div class="text-muted small">Sections</div>
                    <h4 class="mb-0">{{ number_format($totals['sections']) }}</h4>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3 mb-2">
                <div class="card card-body mb-0 bg-light">
                    <div class="text-muted small">Onboarding Completed</div>
                    <h4 class="mb-0">{{ number_format($totals['onboarding_completed_schools']) }} / {{ number_format($totals['schools_in_scope']) }}</h4>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Students (Cumulative)</th>
                        <th>Teachers (Cumulative)</th>
                        <th>Exams (Monthly)</th>
                        <th>Feature Adoption (Classes + Subjects Monthly)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trends['labels'] as $i => $label)
                    @php
                    $studentsVal = (int) ($trends['students_cumulative'][$i] ?? 0);
                    $teachersVal = (int) ($trends['teachers_cumulative'][$i] ?? 0);
                    $examsVal = (int) ($trends['exams_monthly'][$i] ?? 0);
                    $classesVal = (int) ($trends['classes_monthly'][$i] ?? 0);
                    $subjectsVal = (int) ($trends['subjects_monthly'][$i] ?? 0);
                    $adoptionVal = $classesVal + $subjectsVal;

                    $studentWidth = $maxStudent > 0 ? (int) round(($studentsVal / $maxStudent) * 100) : 0;
                    $teacherWidth = $maxTeacher > 0 ? (int) round(($teachersVal / $maxTeacher) * 100) : 0;
                    $examWidth = $maxExam > 0 ? (int) round(($examsVal / $maxExam) * 100) : 0;
                    $adoptionMax = max(1, $maxClass + $maxSubject);
                    $adoptionWidth = (int) round(($adoptionVal / $adoptionMax) * 100);
                    @endphp
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $label)->format('M Y') }}</td>
                        <td>
                            <div class="d-flex align-items-center" style="gap:8px;">
                                <span style="min-width:52px;">{{ number_format($studentsVal) }}</span>
                                <div class="progress flex-grow-1" style="height:8px;">
                                    <div class="progress-bar bg-primary usage-progress" data-width="{{ $studentWidth }}"></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center" style="gap:8px;">
                                <span style="min-width:52px;">{{ number_format($teachersVal) }}</span>
                                <div class="progress flex-grow-1" style="height:8px;">
                                    <div class="progress-bar bg-info usage-progress" data-width="{{ $teacherWidth }}"></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center" style="gap:8px;">
                                <span style="min-width:34px;">{{ number_format($examsVal) }}</span>
                                <div class="progress flex-grow-1" style="height:8px;">
                                    <div class="progress-bar bg-success usage-progress" data-width="{{ $examWidth }}"></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center" style="gap:8px;">
                                <span style="min-width:60px;">{{ number_format($adoptionVal) }}</span>
                                <div class="progress flex-grow-1" style="height:8px;">
                                    <div class="progress-bar bg-indigo usage-progress" data-width="{{ $adoptionWidth }}"></div>
                                </div>
                            </div>
                            <div class="text-muted small mt-1">
                                Classes: {{ number_format($classesVal) }} | Subjects: {{ number_format($subjectsVal) }}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No usage data available for the selected scope and period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.usage-progress').forEach(function(bar) {
            var width = parseInt(bar.getAttribute('data-width') || '0', 10);
            if (isNaN(width) || width < 0) {
                width = 0;
            }
            if (width > 100) {
                width = 100;
            }
            bar.style.width = width + '%';
        });
    });
</script>
@endsection