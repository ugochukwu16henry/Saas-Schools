<?php

namespace App\Services;

use App\Models\PlatformNotification;
use App\Models\School;
use App\User;

class PlatformNotificationService
{
    public function push(string $type, string $title, string $message, ?School $school = null, array $payload = []): PlatformNotification
    {
        return PlatformNotification::create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'school_id' => optional($school)->id,
            'payload' => $payload,
        ]);
    }

    public function schoolRegistered(School $school, User $owner): PlatformNotification
    {
        return $this->push(
            'school_registered',
            'New School Registration',
            $school->name . ' registered by ' . $owner->name . ' (' . $owner->email . ').',
            $school,
            [
                'owner_email' => $owner->email,
                'owner_name' => $owner->name,
            ]
        );
    }

    public function paymentFailure(School $school, int $failureCount, ?string $reason = null): PlatformNotification
    {
        return $this->push(
            'payment_failure',
            'Billing Payment Failure',
            $school->name . ' has ' . $failureCount . ' payment failure(s).' . ($reason ? ' Reason: ' . $reason : ''),
            $school,
            [
                'failure_count' => $failureCount,
                'reason' => $reason,
            ]
        );
    }

    public function schoolSuspendedForBilling(School $school, int $failureCount): PlatformNotification
    {
        return $this->push(
            'billing_suspension',
            'School Suspended for Billing',
            $school->name . ' was suspended after ' . $failureCount . ' failed billing attempt(s).',
            $school,
            [
                'failure_count' => $failureCount,
            ]
        );
    }

    public function planOverrideUpdated(School $school, int $newLimit): PlatformNotification
    {
        return $this->push(
            'plan_override_updated',
            'Plan Override Updated',
            'Free student limit for ' . $school->name . ' changed to ' . $newLimit . '.',
            $school,
            [
                'free_student_limit' => $newLimit,
            ]
        );
    }
}
