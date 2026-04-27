<?php

namespace App\Notifications;

use App\Models\School;
use App\Models\SchoolSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BillingAccountSuspendedNotification extends Notification
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
        $reason = (string) ($this->subscription->last_payment_failure_reason ?: 'Repeated billing payment failures');

        return (new MailMessage)
            ->subject('School account suspended for billing: ' . $this->school->name)
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line('Your school account has been suspended because billing could not be completed in time.')
            ->line('Reason: ' . $reason)
            ->line('Settle your outstanding payment to restore access.')
            ->action('Resolve Billing', route('billing.prompt'));
    }
}
