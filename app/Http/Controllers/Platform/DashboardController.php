<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Services\PlatformNotificationService;
use App\Services\SchoolHealthScoreService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private array $tenantTables = [
        'users',
        'my_classes',
        'sections',
        'subjects',
        'exams',
        'marks',
        'grades',
        'skills',
        'exam_records',
        'student_records',
        'staff_records',
        'payments',
        'payment_records',
        'receipts',
        'time_tables',
        'pins',
        'books',
        'book_requests',
        'settings',
        'dorms',
        'promotions',
    ];

    public function index(Request $request)
    {
        $healthService = app(SchoolHealthScoreService::class);

        $query = $this->buildSchoolsQuery();
        $this->applySchoolFilters($query, $request);
        $this->applySchoolSort($query, $request);

        $schools = $query
            ->paginate(20)
            ->appends($request->query());

        $pageSchools = collect($schools->items());
        $pageMetrics = $this->buildHealthMetricsBySchoolIds($pageSchools->pluck('id')->all());
        $schoolHealthById = $healthService->scoreCollection($pageSchools, $pageMetrics)['by_school'];

        $schoolsIdSubquery = School::query()->select('id');

        $stats = [
            'total_schools' => School::count(),
            'active_schools' => School::where('status', 'active')->count(),
            'trial_schools' => School::where('status', 'trial')->count(),
            'suspended_schools' => School::where('status', 'suspended')->count(),
            // Scope platform-level user totals to users linked to existing schools.
            'students' => DB::table('users')
                ->where('user_type', 'student')
                ->whereIn('school_id', $schoolsIdSubquery)
                ->count(),
            'teachers' => DB::table('users')
                ->where('user_type', 'teacher')
                ->whereIn('school_id', $schoolsIdSubquery)
                ->count(),
            'total_affiliates' => Affiliate::count(),
            'pending_affiliates' => Affiliate::where('status', 'pending')->count(),
            'approved_affiliates' => Affiliate::where('status', 'approved')->count(),
        ];

        $riskCounts = [
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'unknown' => 0,
        ];

        $schoolsForHealth = School::query()
            ->select(['id', 'status', 'onboarding_completed_at'])
            ->with('subscription')
            ->withCount([
                'users as students_count' => function ($q) {
                    $q->where('user_type', 'student');
                },
                'users as teachers_count' => function ($q) {
                    $q->where('user_type', 'teacher');
                },
            ])
            ->get();

        $healthMetrics = $this->buildHealthMetricsBySchoolIds($schoolsForHealth->pluck('id')->all());
        $healthSummary = $healthService->scoreCollection($schoolsForHealth, $healthMetrics);

        $schoolsForRisk = School::query()
            ->select(['id', 'status'])
            ->with([
                'subscription' => function ($q) {
                    $q->select([
                        'id',
                        'school_id',
                        'status',
                        'payment_failures_count',
                        'grace_period_ends_at',
                    ]);
                },
            ])
            ->get();

        foreach ($schoolsForRisk as $schoolRisk) {
            $sub = $schoolRisk->subscription;
            $failureCount = (int) ($sub->payment_failures_count ?? 0);
            $graceEndsAt = optional($sub)->grace_period_ends_at;
            $isGraceExpired = $graceEndsAt ? $graceEndsAt->lte(now()) : false;

            if (!$sub) {
                $riskCounts['unknown']++;
            } elseif (in_array((string) $sub->status, ['expired', 'cancelled'], true) || $schoolRisk->status === 'suspended') {
                $riskCounts['critical']++;
            } elseif ($isGraceExpired || $failureCount >= 2) {
                $riskCounts['high']++;
            } elseif ($failureCount >= 1) {
                $riskCounts['medium']++;
            } else {
                $riskCounts['low']++;
            }
        }

        $stats['risk_critical'] = $riskCounts['critical'];
        $stats['risk_high'] = $riskCounts['high'];
        $stats['risk_medium'] = $riskCounts['medium'];
        $stats['risk_low'] = $riskCounts['low'];
        $stats['risk_unknown'] = $riskCounts['unknown'];
        $stats['health_healthy'] = $healthSummary['distribution']['healthy'] ?? 0;
        $stats['health_watch'] = $healthSummary['distribution']['watch'] ?? 0;
        $stats['health_at_risk'] = $healthSummary['distribution']['at_risk'] ?? 0;
        $stats['health_critical'] = $healthSummary['distribution']['critical'] ?? 0;
        $stats['health_average_score'] = $healthSummary['average_score'] ?? 0;

        $pendingAffiliates = Affiliate::where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get();

        return view('platform.dashboard.index', compact('schools', 'stats', 'pendingAffiliates', 'schoolHealthById'));
    }

    public function exportSchoolsCsv(Request $request)
    {
        $healthService = app(SchoolHealthScoreService::class);

        $query = $this->buildSchoolsQuery();
        $this->applySchoolFilters($query, $request);
        $this->applySchoolSort($query, $request);

        $schools = $query->get();
        $healthMetrics = $this->buildHealthMetricsBySchoolIds($schools->pluck('id')->all());
        $healthBySchoolId = $healthService->scoreCollection($schools, $healthMetrics)['by_school'];
        $filename = 'schools_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($schools, $healthBySchoolId) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'School Name',
                'Slug',
                'Status',
                'Health Score',
                'Health Band',
                'Billing Risk',
                'Subscription Status',
                'Payment Failures',
                'Grace Period Ends At',
                'Teachers',
                'Students',
                'Total Users',
                'Email',
                'Phone',
                'Address',
                'Created At',
            ]);

            foreach ($schools as $school) {
                $subscription = $school->subscription;
                $riskLabel = $this->determineRiskLabel($school);
                $health = $healthBySchoolId[$school->id] ?? ['score' => 0, 'label' => 'Critical'];

                fputcsv($out, [
                    $school->name,
                    $school->slug,
                    $school->status,
                    (int) ($health['score'] ?? 0),
                    (string) ($health['label'] ?? 'Critical'),
                    $riskLabel,
                    $subscription ? $subscription->status : 'none',
                    (int) ($subscription->payment_failures_count ?? 0),
                    optional($subscription?->grace_period_ends_at)->toDateTimeString(),
                    (int) $school->teachers_count,
                    (int) $school->students_count,
                    (int) $school->total_users_count,
                    $school->email,
                    $school->phone,
                    $school->address,
                    optional($school->created_at)->toDateTimeString(),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportAtRiskContactsCsv(Request $request)
    {
        $query = $this->buildSchoolsQuery();

        $requestWithAtRisk = $request->duplicate(array_merge($request->query(), [
            'at_risk' => '1',
            'sort' => $request->get('sort', 'risk'),
        ]));

        $this->applySchoolFilters($query, $requestWithAtRisk);
        $this->applySchoolSort($query, $requestWithAtRisk);

        $schools = $query->get();
        $filename = 'at_risk_contacts_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($schools) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'School Name',
                'Slug',
                'Billing Risk',
                'Subscription Status',
                'Payment Failures',
                'Grace Period Ends At',
                'School Status',
                'Email',
                'Phone',
                'Address',
            ]);

            foreach ($schools as $school) {
                $subscription = $school->subscription;

                fputcsv($out, [
                    $school->name,
                    $school->slug,
                    $this->determineRiskLabel($school),
                    $subscription ? $subscription->status : 'none',
                    (int) ($subscription->payment_failures_count ?? 0),
                    optional($subscription?->grace_period_ends_at)->toDateTimeString(),
                    $school->status,
                    $school->email,
                    $school->phone,
                    $school->address,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function atRiskContactGaps(Request $request)
    {
        $query = $this->buildSchoolsQuery();

        $requestWithAtRisk = $request->duplicate(array_merge($request->query(), [
            'at_risk' => '1',
            'sort' => $request->get('sort', 'risk'),
        ]));

        $this->applySchoolFilters($query, $requestWithAtRisk);
        $this->applySchoolSort($query, $requestWithAtRisk);

        $query->where(function ($q) {
            $q->whereNull('schools.email')
                ->orWhere('schools.email', '')
                ->orWhereNull('schools.phone')
                ->orWhere('schools.phone', '');
        });

        $schools = $query->paginate(20)->appends($request->query());

        return view('platform.dashboard.contact_gaps', compact('schools'));
    }

    private function buildSchoolsQuery(): Builder
    {
        return School::query()
            ->leftJoin('school_subscriptions as ss', 'ss.school_id', '=', 'schools.id')
            ->select('schools.*')
            ->with('subscription')
            ->withCount([
                'users as total_users_count',
                'users as students_count' => function ($q) {
                    $q->where('user_type', 'student');
                },
                'users as teachers_count' => function ($q) {
                    $q->where('user_type', 'teacher');
                },
            ]);
    }

    private function applySchoolFilters(Builder $query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            });
        }

        if ($request->filled('risk')) {
            $risk = strtolower(trim((string) $request->risk));

            if ($risk === 'critical') {
                $query->where(function ($q) {
                    $q->where('status', 'suspended')
                        ->orWhereHas('subscription', function ($sq) {
                            $sq->whereIn('status', ['expired', 'cancelled']);
                        });
                });
            }

            if ($risk === 'high') {
                $query->where('status', '!=', 'suspended')
                    ->whereHas('subscription', function ($sq) {
                        $sq->whereNotIn('status', ['expired', 'cancelled'])
                            ->where(function ($hq) {
                                $hq->where('payment_failures_count', '>=', 2)
                                    ->orWhere(function ($gq) {
                                        $gq->whereNotNull('grace_period_ends_at')
                                            ->where('grace_period_ends_at', '<=', now());
                                    });
                            });
                    });
            }

            if ($risk === 'medium') {
                $query->where('status', '!=', 'suspended')
                    ->whereHas('subscription', function ($sq) {
                        $sq->whereNotIn('status', ['expired', 'cancelled'])
                            ->where('payment_failures_count', '=', 1);
                    });
            }

            if ($risk === 'low') {
                $query->where('status', '!=', 'suspended')
                    ->whereHas('subscription', function ($sq) {
                        $sq->whereIn('status', ['active', 'trialling'])
                            ->where('payment_failures_count', '=', 0)
                            ->where(function ($lq) {
                                $lq->whereNull('grace_period_ends_at')
                                    ->orWhere('grace_period_ends_at', '>', now());
                            });
                    });
            }

            if ($risk === 'unknown') {
                $query->doesntHave('subscription');
            }
        }

        if ($request->boolean('at_risk')) {
            $query->where(function ($q) {
                $q->where('status', 'suspended')
                    ->orWhereHas('subscription', function ($sq) {
                        $sq->whereIn('status', ['expired', 'cancelled'])
                            ->orWhere(function ($hq) {
                                $hq->whereNotIn('status', ['expired', 'cancelled'])
                                    ->where(function ($rq) {
                                        $rq->where('payment_failures_count', '>=', 2)
                                            ->orWhere(function ($gq) {
                                                $gq->whereNotNull('grace_period_ends_at')
                                                    ->where('grace_period_ends_at', '<=', now());
                                            });
                                    });
                            });
                    });
            });
        }
    }

    private function applySchoolSort(Builder $query, Request $request): void
    {
        $riskOrderSql = "CASE
            WHEN schools.status = 'suspended' THEN 1
            WHEN ss.status IN ('expired', 'cancelled') THEN 1
            WHEN ((ss.grace_period_ends_at IS NOT NULL AND ss.grace_period_ends_at <= ?) OR COALESCE(ss.payment_failures_count, 0) >= 2) THEN 2
            WHEN COALESCE(ss.payment_failures_count, 0) >= 1 THEN 3
            WHEN ss.id IS NULL THEN 5
            ELSE 4
        END";

        $sort = strtolower((string) $request->get('sort', 'risk'));
        if (!in_array($sort, ['risk', 'newest', 'oldest', 'name'], true)) {
            $sort = 'risk';
        }

        if ($sort === 'name') {
            $query->orderBy('schools.name');
        } elseif ($sort === 'oldest') {
            $query->orderBy('schools.created_at');
        } elseif ($sort === 'newest') {
            $query->orderByDesc('schools.created_at');
        } else {
            $query->orderByRaw($riskOrderSql, [now()])
                ->orderByDesc('schools.created_at');
        }
    }

    private function determineRiskLabel($school): string
    {
        $sub = $school->subscription;
        $failureCount = (int) ($sub->payment_failures_count ?? 0);
        $graceEndsAt = optional($sub)->grace_period_ends_at;
        $isGraceExpired = $graceEndsAt ? $graceEndsAt->lte(now()) : false;

        if (!$sub) {
            return 'Unknown';
        }

        if (in_array((string) $sub->status, ['expired', 'cancelled'], true) || $school->status === 'suspended') {
            return 'Critical';
        }

        if ($isGraceExpired || $failureCount >= 2) {
            return 'High';
        }

        if ($failureCount >= 1) {
            return 'Medium';
        }

        return 'Low';
    }

    public function show(School $school)
    {
        $healthService = app(SchoolHealthScoreService::class);

        $school->load('subscription')
            ->loadCount([
                'users as total_users_count',
                'users as students_count' => function ($q) {
                    $q->where('user_type', 'student');
                },
                'users as teachers_count' => function ($q) {
                    $q->where('user_type', 'teacher');
                },
                'users as admins_count' => function ($q) {
                    $q->whereIn('user_type', ['admin', 'super_admin']);
                },
            ]);

        $healthMetrics = $this->buildHealthMetricsBySchoolIds([$school->id]);
        $health = $healthService->scoreSchool($school, $healthMetrics[$school->id] ?? []);

        return view('platform.dashboard.show', compact('school', 'health'));
    }

    /**
     * @param  array<int, int>  $schoolIds
     * @return array<int, array<string, int>>
     */
    private function buildHealthMetricsBySchoolIds(array $schoolIds): array
    {
        $schoolIds = array_values(array_unique(array_filter(array_map('intval', $schoolIds), function ($id) {
            return $id > 0;
        })));

        if ($schoolIds === []) {
            return [];
        }

        $recentUsers = DB::table('users')
            ->selectRaw('school_id, COUNT(*) as aggregate')
            ->whereIn('school_id', $schoolIds)
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('school_id')
            ->pluck('aggregate', 'school_id')
            ->map(function ($value) {
                return (int) $value;
            })
            ->all();

        $classes = DB::table('my_classes')
            ->selectRaw('school_id, COUNT(*) as aggregate')
            ->whereIn('school_id', $schoolIds)
            ->groupBy('school_id')
            ->pluck('aggregate', 'school_id')
            ->map(function ($value) {
                return (int) $value;
            })
            ->all();

        $subjects = DB::table('subjects')
            ->selectRaw('school_id, COUNT(*) as aggregate')
            ->whereIn('school_id', $schoolIds)
            ->groupBy('school_id')
            ->pluck('aggregate', 'school_id')
            ->map(function ($value) {
                return (int) $value;
            })
            ->all();

        $exams = DB::table('exams')
            ->selectRaw('school_id, COUNT(*) as aggregate')
            ->whereIn('school_id', $schoolIds)
            ->groupBy('school_id')
            ->pluck('aggregate', 'school_id')
            ->map(function ($value) {
                return (int) $value;
            })
            ->all();

        $out = [];
        foreach ($schoolIds as $schoolId) {
            $out[$schoolId] = [
                'recent_users_30d' => $recentUsers[$schoolId] ?? 0,
                'classes_count' => $classes[$schoolId] ?? 0,
                'subjects_count' => $subjects[$schoolId] ?? 0,
                'exams_count' => $exams[$schoolId] ?? 0,
            ];
        }

        return $out;
    }

    public function suspend(School $school)
    {
        $school->update(['status' => 'suspended']);

        return back()->with('status', "{$school->name} has been suspended.");
    }

    public function activate(School $school)
    {
        $school->update(['status' => 'active']);

        return back()->with('status', "{$school->name} has been activated.");
    }

    public function updatePlan(Request $request, School $school)
    {
        $request->validate([
            'free_student_limit' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $school->update(['free_student_limit' => $request->integer('free_student_limit')]);
        app(PlatformNotificationService::class)->planOverrideUpdated($school, (int) $school->free_student_limit);

        return back()->with('status', "Free student limit updated to {$school->free_student_limit} for {$school->name}.");
    }

    public function destroy(School $school)
    {
        DB::transaction(function () use ($school) {
            SchoolSubscription::where('school_id', $school->id)->delete();

            foreach ($this->tenantTables as $table) {
                DB::table($table)->where('school_id', $school->id)->delete();
            }

            $school->delete();
        });

        return redirect()->route('platform.dashboard')
            ->with('status', 'School and all tenant data deleted successfully.');
    }
}
