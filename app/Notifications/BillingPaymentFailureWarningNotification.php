<?php

namespace App\Notifications;

use App\Models\School;
use App\Models\SchoolSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BillingPaymentFailureWarningNotification extends Notification
{
    use Queueable;

    private School $school;
    private SchoolSubscription $subscription;

    public function __construct(School $school, SchoolSubscription $subscription)
    {
        $this->school = $school;
        $this->subscription = $subscription;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $failures = (int) $this->subscription->payment_failures_count;
        $graceEndsAt = optional($this->subscription->grace_period_ends_at)->format('d M Y H:i') ?: 'soon';

        return (new MailMessage)
            ->subject('Billing payment failed for ' . $this->school->name)
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line('We could not process a recent billing payment for your school account.')
            ->line('Failure attempts: ' . $failures)
            ->line('Grace period ends: ' . $graceEndsAt)
            ->line('Please complete payment to prevent automatic suspension.')
            ->action('Open Billing', route('billing.prompt'));
    }
}
