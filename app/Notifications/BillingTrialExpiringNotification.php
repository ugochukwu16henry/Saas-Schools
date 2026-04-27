<?php

namespace App\Notifications;

use App\Models\School;
use App\Models\SchoolSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BillingTrialExpiringNotification extends Notification
{
    use Queueable;

    private School $school;
    private SchoolSubscription $subscription;
    private int $daysRemaining;

    public function __construct(School $school, SchoolSubscription $subscription, int $daysRemaining)
    {
        $this->school = $school;
        $this->subscription = $subscription;
        $this->daysRemaining = max(0, $daysRemaining);
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $trialEndsAt = optional($this->subscription->trial_ends_at)->format('d M Y H:i') ?: 'soon';
        $daysText = $this->daysRemaining === 1 ? '1 day' : $this->daysRemaining . ' days';

        return (new MailMessage)
            ->subject('Trial ending soon for ' . $this->school->name)
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line('Your school trial is ending in ' . $daysText . '.')
            ->line('Trial end date: ' . $trialEndsAt)
            ->line('Add a payment method and complete billing to avoid suspension.')
            ->action('Open Billing', route('billing.prompt'));
    }
}
