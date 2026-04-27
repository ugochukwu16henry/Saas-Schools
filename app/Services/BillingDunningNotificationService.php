<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolSubscription;
use App\Notifications\BillingAccountSuspendedNotification;
use App\Notifications\BillingPaymentFailureWarningNotification;
use App\Notifications\BillingTrialExpiringNotification;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class BillingDunningNotificationService
{
    public function sendPaymentFailureWarning(School $school, SchoolSubscription $subscription): void
    {
        $recipients = $this->schoolBillingRecipients($school->id);
        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new BillingPaymentFailureWarningNotification($school, $subscription));

        Log::info('Billing warning notifications sent.', [
            'school_id' => $school->id,
            'subscription_id' => $subscription->id,
            'recipients' => $recipients->count(),
        ]);
    }

    public function sendSuspensionNotice(School $school, SchoolSubscription $subscription): void
    {
        $recipients = $this->schoolBillingRecipients($school->id);
        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new BillingAccountSuspendedNotification($school, $subscription));

        Log::warning('Billing suspension notifications sent.', [
            'school_id' => $school->id,
            'subscription_id' => $subscription->id,
            'recipients' => $recipients->count(),
        ]);
    }

    public function sendTrialExpiringWarning(School $school, SchoolSubscription $subscription, int $daysRemaining): void
    {
        $recipients = $this->schoolBillingRecipients($school->id);
        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new BillingTrialExpiringNotification($school, $subscription, $daysRemaining));

        Log::info('Trial expiry warning notifications sent.', [
            'school_id' => $school->id,
            'subscription_id' => $subscription->id,
            'days_remaining' => $daysRemaining,
            'recipients' => $recipients->count(),
        ]);
    }

    private function schoolBillingRecipients(int $schoolId): Collection
    {
        return User::query()
            ->where('school_id', $schoolId)
            ->whereIn('user_type', ['super_admin', 'admin'])
            ->whereNotNull('email')
            ->get();
    }
}
