<?php

namespace App\Jobs;

use App\Models\School;
use App\Models\SchoolSubscription;
use App\Notifications\BillingAccountSuspendedNotification;
use App\Notifications\BillingPaymentFailureWarningNotification;
use App\Notifications\BillingTrialExpiringNotification;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendSchoolBillingNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 20;

    private string $type;
    private int $schoolId;
    private int $subscriptionId;
    private int $daysRemaining;

    public function __construct(string $type, int $schoolId, int $subscriptionId, int $daysRemaining = 0)
    {
        $this->type = $type;
        $this->schoolId = $schoolId;
        $this->subscriptionId = $subscriptionId;
        $this->daysRemaining = $daysRemaining;
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $school = School::query()->find($this->schoolId);
        $subscription = SchoolSubscription::query()->find($this->subscriptionId);
        if (! $school || ! $subscription) {
            return;
        }

        $recipients = User::query()
            ->where('school_id', $school->id)
            ->whereIn('user_type', ['super_admin', 'admin'])
            ->whereNotNull('email')
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        if ($this->type === 'payment_warning') {
            Notification::send($recipients, new BillingPaymentFailureWarningNotification($school, $subscription));
            return;
        }

        if ($this->type === 'suspension_notice') {
            Notification::send($recipients, new BillingAccountSuspendedNotification($school, $subscription));
            return;
        }

        if ($this->type === 'trial_expiring') {
            Notification::send($recipients, new BillingTrialExpiringNotification($school, $subscription, $this->daysRemaining));
        }
    }
}
