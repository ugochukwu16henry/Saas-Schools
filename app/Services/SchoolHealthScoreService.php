<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Support\Collection;

class SchoolHealthScoreService
{
    /**
     * @param  array<string, int>  $metrics
     * @return array<string, mixed>
     */
    public function scoreSchool(School $school, array $metrics = []): array
    {
        $score = 50;
        $drivers = [];

        $subscription = $school->subscription;
        $status = (string) ($subscription->status ?? 'none');
        $failures = (int) ($subscription->payment_failures_count ?? 0);
        $graceEndsAt = optional($subscription)->grace_period_ends_at;
        $isGraceExpired = $graceEndsAt ? $graceEndsAt->lte(now()) : false;

        if ($school->status === 'suspended') {
            $score -= 35;
            $drivers[] = 'School is suspended';
        } elseif ($school->status === 'active') {
            $score += 5;
        }

        if (! $subscription) {
            $score -= 15;
            $drivers[] = 'No subscription record';
        } elseif (in_array($status, ['expired', 'cancelled'], true)) {
            $score -= 30;
            $drivers[] = 'Subscription is ' . $status;
        } elseif ($isGraceExpired || $failures >= 2) {
            $score -= 20;
            $drivers[] = 'Billing retries are escalating';
        } elseif ($failures === 1) {
            $score -= 10;
            $drivers[] = 'Recent payment failure';
        } else {
            $score += 10;
        }

        if ($school->onboarding_completed_at) {
            $score += 10;
        } else {
            $score -= 10;
            $drivers[] = 'Onboarding checklist not completed';
        }

        $students = (int) ($school->students_count ?? 0);
        $teachers = (int) ($school->teachers_count ?? 0);

        if ($students >= 50) {
            $score += 12;
        } elseif ($students >= 10) {
            $score += 8;
        } elseif ($students >= 1) {
            $score += 3;
        } else {
            $score -= 8;
            $drivers[] = 'No students onboarded yet';
        }

        if ($teachers >= 5) {
            $score += 8;
        } elseif ($teachers >= 1) {
            $score += 4;
        } else {
            $score -= 6;
            $drivers[] = 'No teachers onboarded yet';
        }

        $recentUsers = (int) ($metrics['recent_users_30d'] ?? 0);
        if ($recentUsers >= 20) {
            $score += 10;
        } elseif ($recentUsers >= 5) {
            $score += 6;
        } elseif ($recentUsers >= 1) {
            $score += 2;
        } else {
            $score -= 4;
            $drivers[] = 'No new users in the last 30 days';
        }

        $classes = (int) ($metrics['classes_count'] ?? 0);
        $subjects = (int) ($metrics['subjects_count'] ?? 0);
        $exams = (int) ($metrics['exams_count'] ?? 0);

        if ($classes > 0 && $subjects > 0) {
            $score += 8;
        } else {
            $score -= 6;
            $drivers[] = 'Core setup incomplete (classes or subjects missing)';
        }

        if ($exams > 0) {
            $score += 5;
        }

        $score = max(0, min(100, $score));
        $band = $this->bandForScore($score);

        if ($band === 'critical' && ! in_array('Urgent intervention needed', $drivers, true)) {
            $drivers[] = 'Urgent intervention needed';
        }

        return [
            'score' => $score,
            'band' => $band,
            'label' => $this->labelForBand($band),
            'badge' => $this->badgeForBand($band),
            'drivers' => array_slice($drivers, 0, 4),
            'metrics' => [
                'recent_users_30d' => $recentUsers,
                'classes_count' => $classes,
                'subjects_count' => $subjects,
                'exams_count' => $exams,
            ],
        ];
    }

    /**
     * @param  Collection<int, School>  $schools
     * @param  array<int, array<string, int>>  $metricsBySchoolId
     * @return array{by_school: array<int, array<string, mixed>>, distribution: array<string, int>, average_score: int}
     */
    public function scoreCollection(Collection $schools, array $metricsBySchoolId = []): array
    {
        $bySchool = [];
        $distribution = [
            'healthy' => 0,
            'watch' => 0,
            'at_risk' => 0,
            'critical' => 0,
        ];
        $sum = 0;

        foreach ($schools as $school) {
            $health = $this->scoreSchool($school, $metricsBySchoolId[$school->id] ?? []);
            $bySchool[$school->id] = $health;
            $distribution[$health['band']]++;
            $sum += (int) $health['score'];
        }

        $average = $schools->count() > 0 ? (int) round($sum / $schools->count()) : 0;

        return [
            'by_school' => $bySchool,
            'distribution' => $distribution,
            'average_score' => $average,
        ];
    }

    private function bandForScore(int $score): string
    {
        if ($score >= 80) {
            return 'healthy';
        }

        if ($score >= 60) {
            return 'watch';
        }

        if ($score >= 40) {
            return 'at_risk';
        }

        return 'critical';
    }

    private function labelForBand(string $band): string
    {
        if ($band === 'healthy') {
            return 'Healthy';
        }

        if ($band === 'watch') {
            return 'Watch';
        }

        if ($band === 'at_risk') {
            return 'At Risk';
        }

        return 'Critical';
    }

    private function badgeForBand(string $band): string
    {
        if ($band === 'healthy') {
            return 'success';
        }

        if ($band === 'watch') {
            return 'info';
        }

        if ($band === 'at_risk') {
            return 'warning';
        }

        return 'danger';
    }
}
