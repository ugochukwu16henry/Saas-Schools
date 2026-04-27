<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\School;
use App\Models\SchoolSubscription;
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
        $query = School::query()
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

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $term = trim($request->q);
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

        $riskOrderSql = "CASE
            WHEN schools.status = 'suspended' THEN 1
            WHEN ss.status IN ('expired', 'cancelled') THEN 1
            WHEN ((ss.grace_period_ends_at IS NOT NULL AND ss.grace_period_ends_at <= ?) OR COALESCE(ss.payment_failures_count, 0) >= 2) THEN 2
            WHEN COALESCE(ss.payment_failures_count, 0) >= 1 THEN 3
            WHEN ss.id IS NULL THEN 5
            ELSE 4
        END";

        $schools = $query
            ->orderByRaw($riskOrderSql, [now()])
            ->orderByDesc('schools.created_at')
            ->paginate(20)
            ->appends($request->query());

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

        $pendingAffiliates = Affiliate::where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get();

        return view('platform.dashboard.index', compact('schools', 'stats', 'pendingAffiliates'));
    }

    public function show(School $school)
    {
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

        return view('platform.dashboard.show', compact('school'));
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
