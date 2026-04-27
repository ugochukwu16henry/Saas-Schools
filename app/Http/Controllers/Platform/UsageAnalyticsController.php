<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsageAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
            'months' => ['nullable', 'integer', 'min:3', 'max:24'],
        ]);

        $months = (int) ($validated['months'] ?? 12);
        $selectedSchoolId = isset($validated['school_id']) ? (int) $validated['school_id'] : null;

        $schools = School::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $scopeSchoolIds = $selectedSchoolId ? [$selectedSchoolId] : $schools->pluck('id')->map(function ($id) {
            return (int) $id;
        })->all();

        $selectedSchool = $selectedSchoolId
            ? $schools->firstWhere('id', $selectedSchoolId)
            : null;

        $endMonth = now()->startOfMonth();
        $startMonth = (clone $endMonth)->subMonths($months - 1);

        $monthLabels = [];
        $cursor = (clone $startMonth);
        for ($i = 0; $i < $months; $i++) {
            $monthLabels[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $totals = $this->buildTotals($scopeSchoolIds);

        $studentsMonthly = $this->monthlyCounts('users', $scopeSchoolIds, $startMonth, $endMonth, 'student');
        $teachersMonthly = $this->monthlyCounts('users', $scopeSchoolIds, $startMonth, $endMonth, 'teacher');
        $examsMonthly = $this->monthlyCounts('exams', $scopeSchoolIds, $startMonth, $endMonth);
        $classesMonthly = $this->monthlyCounts('my_classes', $scopeSchoolIds, $startMonth, $endMonth);
        $subjectsMonthly = $this->monthlyCounts('subjects', $scopeSchoolIds, $startMonth, $endMonth);

        $studentsBase = $this->countBeforeMonth('users', $scopeSchoolIds, $startMonth, 'student');
        $teachersBase = $this->countBeforeMonth('users', $scopeSchoolIds, $startMonth, 'teacher');

        $studentsCumulative = $this->toCumulativeSeries($monthLabels, $studentsMonthly, $studentsBase);
        $teachersCumulative = $this->toCumulativeSeries($monthLabels, $teachersMonthly, $teachersBase);

        $trends = [
            'labels' => $monthLabels,
            'students_cumulative' => $studentsCumulative,
            'teachers_cumulative' => $teachersCumulative,
            'exams_monthly' => $this->seriesFromMonthly($monthLabels, $examsMonthly),
            'classes_monthly' => $this->seriesFromMonthly($monthLabels, $classesMonthly),
            'subjects_monthly' => $this->seriesFromMonthly($monthLabels, $subjectsMonthly),
        ];

        return view('platform.usage.index', [
            'schools' => $schools,
            'selectedSchool' => $selectedSchool,
            'selectedSchoolId' => $selectedSchoolId,
            'months' => $months,
            'totals' => $totals,
            'trends' => $trends,
        ]);
    }

    /**
     * @param  array<int, int>  $schoolIds
     * @return array<string, int>
     */
    private function buildTotals(array $schoolIds): array
    {
        if ($schoolIds === []) {
            return [
                'students' => 0,
                'teachers' => 0,
                'exams' => 0,
                'classes' => 0,
                'subjects' => 0,
                'sections' => 0,
                'new_users_30d' => 0,
                'new_exams_30d' => 0,
                'onboarding_completed_schools' => 0,
                'schools_in_scope' => 0,
            ];
        }

        return [
            'students' => (int) DB::table('users')->whereIn('school_id', $schoolIds)->where('user_type', 'student')->count(),
            'teachers' => (int) DB::table('users')->whereIn('school_id', $schoolIds)->where('user_type', 'teacher')->count(),
            'exams' => (int) DB::table('exams')->whereIn('school_id', $schoolIds)->count(),
            'classes' => (int) DB::table('my_classes')->whereIn('school_id', $schoolIds)->count(),
            'subjects' => (int) DB::table('subjects')->whereIn('school_id', $schoolIds)->count(),
            'sections' => (int) DB::table('sections')->whereIn('school_id', $schoolIds)->count(),
            'new_users_30d' => (int) DB::table('users')->whereIn('school_id', $schoolIds)->where('created_at', '>=', now()->subDays(30))->count(),
            'new_exams_30d' => (int) DB::table('exams')->whereIn('school_id', $schoolIds)->where('created_at', '>=', now()->subDays(30))->count(),
            'onboarding_completed_schools' => (int) DB::table('schools')->whereIn('id', $schoolIds)->whereNotNull('onboarding_completed_at')->count(),
            'schools_in_scope' => count($schoolIds),
        ];
    }

    /**
     * @param  array<int, int>  $schoolIds
     * @return array<string, int>
     */
    private function monthlyCounts(string $table, array $schoolIds, $startMonth, $endMonth, ?string $userType = null): array
    {
        if ($schoolIds === []) {
            return [];
        }

        $query = DB::table($table)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
            ->whereIn('school_id', $schoolIds)
            ->whereNotNull('created_at')
            ->whereBetween('created_at', [$startMonth, (clone $endMonth)->endOfMonth()])
            ->groupBy('ym');

        if ($table === 'users' && $userType !== null) {
            $query->where('user_type', $userType);
        }

        return $query
            ->pluck('total', 'ym')
            ->map(function ($value) {
                return (int) $value;
            })
            ->all();
    }

    /**
     * @param  array<int, int>  $schoolIds
     */
    private function countBeforeMonth(string $table, array $schoolIds, $startMonth, ?string $userType = null): int
    {
        if ($schoolIds === []) {
            return 0;
        }

        $query = DB::table($table)
            ->whereIn('school_id', $schoolIds)
            ->whereNotNull('created_at')
            ->where('created_at', '<', $startMonth);

        if ($table === 'users' && $userType !== null) {
            $query->where('user_type', $userType);
        }

        return (int) $query->count();
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<string, int>  $monthly
     * @return array<int, int>
     */
    private function seriesFromMonthly(array $labels, array $monthly): array
    {
        $series = [];
        foreach ($labels as $label) {
            $series[] = (int) ($monthly[$label] ?? 0);
        }

        return $series;
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<string, int>  $monthly
     * @return array<int, int>
     */
    private function toCumulativeSeries(array $labels, array $monthly, int $base): array
    {
        $series = [];
        $running = $base;

        foreach ($labels as $label) {
            $running += (int) ($monthly[$label] ?? 0);
            $series[] = $running;
        }

        return $series;
    }
}
