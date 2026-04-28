<?php

namespace App\Services;

use App\Jobs\SendSchoolBillingNotificationJob;
use App\Models\School;
use App\Models\SchoolSubscription;
use Illuminate\Support\Facades\Log;

class BillingDunningNotificationService
{
    public function sendPaymentFailureWarning(School $school, SchoolSubscription $subscription): void
    {
        SendSchoolBillingNotificationJob::dispatch('payment_warning', $school->id, $subscription->id);

        Log::info('Billing warning notifications sent.', [
            'school_id' => $school->id,
            'subscription_id' => $subscription->id,
            'queued' => true,
        ]);
    }

    public function sendSuspensionNotice(School $school, SchoolSubscription $subscription): void
    {
        SendSchoolBillingNotificationJob::dispatch('suspension_notice', $school->id, $subscription->id);

        Log::warning('Billing suspension notifications sent.', [
            'school_id' => $school->id,
            'subscription_id' => $subscription->id,
            'queued' => true,
        ]);
    }

    public function sendTrialExpiringWarning(School $school, SchoolSubscription $subscription, int $daysRemaining): void
    {
        SendSchoolBillingNotificationJob::dispatch('trial_expiring', $school->id, $subscription->id, $daysRemaining);

        Log::info('Trial expiry warning notifications sent.', [
            'school_id' => $school->id,
            'subscription_id' => $subscription->id,
            'days_remaining' => $daysRemaining,
            'queued' => true,
        ]);
    }

    // Recipients are resolved inside the queued job to keep this service async.
}
