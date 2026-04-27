<?php

namespace App\Console\Commands;

use App\Models\Affiliate;
use App\Models\AffiliateCommissionLedger;
use App\Models\AffiliatePayout;
use App\Models\PlatformAdmin;
use App\Models\School;
use App\Notifications\PlatformDigestNotification;
use App\Services\SchoolHealthScoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendPlatformDigest extends Command
{
    protected $signature = 'platform:send-digest {--period=auto : daily|weekly|auto}';

    protected $description = 'Send daily or weekly platform KPI digest email to platform admins/recipients.';

    public function handle(): int
    {
        $period = strtolower((string) $this->option('period'));
        if (! in_array($period, ['daily', 'weekly', 'auto'], true)) {
            $this->error('Invalid --period. Use daily, weekly, or auto.');

            return self::FAILURE;
        }

        if ($period === 'auto') {
            $period = strtolower((string) config('platform.digest.frequency', 'daily'));
            if (! in_array($period, ['daily', 'weekly'], true)) {
                $period = 'daily';
            }
        }

        $windowDays = $period === 'weekly' ? 7 : 1;
        $windowStart = now()->subDays($windowDays);

        $recipients = $this->resolveRecipients();
        if ($recipients === []) {
            $this->warn('No digest recipients configured. Skipping send.');

            return self::SUCCESS;
        }

        $schools = School::query()
            ->select(['id', 'status', 'free_student_limit', 'onboarding_completed_at'])
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

        $schoolIds = $schools->pluck('id')->map(function ($id) {
            return (int) $id;
        })->all();

        $usersTable = DB::table('users');
        if ($schoolIds !== []) {
            $usersTable->whereIn('school_id', $schoolIds);
        }

        $students = (clone $usersTable)->where('user_type', 'student')->count();
        $teachers = (clone $usersTable)->where('user_type', 'teacher')->count();
        $newUsersInWindow = (clone $usersTable)->where('created_at', '>=', $windowStart)->count();

        $risk = [
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'unknown' => 0,
        ];

        $estimatedBillableStudents = 0;
        foreach ($schools as $school) {
            $subscription = $school->subscription;
            $failureCount = (int) ($subscription->payment_failures_count ?? 0);
            $graceEndsAt = optional($subscription)->grace_period_ends_at;
            $isGraceExpired = $graceEndsAt ? $graceEndsAt->lte(now()) : false;

            if (! $subscription) {
                $risk['unknown']++;
            } elseif (in_array((string) $subscription->status, ['expired', 'cancelled'], true) || $school->status === 'suspended') {
                $risk['critical']++;
            } elseif ($isGraceExpired || $failureCount >= 2) {
                $risk['high']++;
            } elseif ($failureCount >= 1) {
                $risk['medium']++;
            } else {
                $risk['low']++;
            }

            $estimatedBillableStudents += max(0, (int) $school->students_count - (int) $school->free_student_limit);
        }

        $healthService = app(SchoolHealthScoreService::class);
        $healthMetrics = $this->buildHealthMetricsBySchoolIds($schoolIds);
        $healthSummary = $healthService->scoreCollection($schools, $healthMetrics);

        $summary = [
            'window_days' => $windowDays,
            'schools' => [
                'total' => (int) $schools->count(),
                'active' => (int) $schools->where('status', 'active')->count(),
                'trial' => (int) $schools->where('status', 'trial')->count(),
                'suspended' => (int) $schools->where('status', 'suspended')->count(),
                'new_in_window' => (int) School::query()->where('created_at', '>=', $windowStart)->count(),
            ],
            'users' => [
                'students' => (int) $students,
                'teachers' => (int) $teachers,
                'new_in_window' => (int) $newUsersInWindow,
            ],
            'billing' => [
                'estimated_billable_students' => (int) $estimatedBillableStudents,
                'projected_mrr_ngn' => (int) ($estimatedBillableStudents * 100),
                'at_risk_schools' => (int) ($risk['critical'] + $risk['high']),
            ],
            'health' => [
                'healthy' => (int) ($healthSummary['distribution']['healthy'] ?? 0),
                'watch' => (int) ($healthSummary['distribution']['watch'] ?? 0),
                'at_risk' => (int) ($healthSummary['distribution']['at_risk'] ?? 0),
                'critical' => (int) ($healthSummary['distribution']['critical'] ?? 0),
                'average_score' => (int) ($healthSummary['average_score'] ?? 0),
            ],
            'affiliate' => [
                'approved' => (int) Affiliate::query()->where('status', 'approved')->count(),
                'pending' => (int) Affiliate::query()->where('status', 'pending')->count(),
                'commission_in_window_ngn' => (int) AffiliateCommissionLedger::query()->where('created_at', '>=', $windowStart)->sum('total_commission_ngn'),
                'pending_payouts_ngn' => (int) AffiliatePayout::query()->where('status', 'pending')->sum('amount_ngn'),
            ],
        ];

        foreach ($recipients as $email) {
            Notification::route('mail', $email)
                ->notify(new PlatformDigestNotification($summary, ucfirst($period)));
        }

        $this->info('Platform digest sent to ' . count($recipients) . ' recipient(s) for ' . $period . ' period.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function resolveRecipients(): array
    {
        $fromConfig = array_values(array_filter(array_map('trim', explode(',', (string) config('platform.digest.recipients', '')))));

        $recipients = [];
        foreach ($fromConfig as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = strtolower($email);
            }
        }

        if ((bool) config('platform.digest.include_platform_admins', true)) {
            $adminEmails = PlatformAdmin::query()
                ->whereNotNull('email')
                ->pluck('email')
                ->filter(function ($email) {
                    return filter_var($email, FILTER_VALIDATE_EMAIL);
                })
                ->map(function ($email) {
                    return strtolower((string) $email);
                })
                ->all();

            $recipients = array_merge($recipients, $adminEmails);
        }

        return array_values(array_unique($recipients));
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
}
