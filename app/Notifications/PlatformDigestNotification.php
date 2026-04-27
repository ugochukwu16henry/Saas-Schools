<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlatformDigestNotification extends Notification
{
    use Queueable;

    /**
     * @var array<string, mixed>
     */
    private array $summary;

    private string $periodLabel;

    /**
     * @param  array<string, mixed>  $summary
     */
    public function __construct(array $summary, string $periodLabel)
    {
        $this->summary = $summary;
        $this->periodLabel = $periodLabel;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $school = $this->summary['schools'];
        $users = $this->summary['users'];
        $billing = $this->summary['billing'];
        $health = $this->summary['health'];
        $affiliate = $this->summary['affiliate'];

        return (new MailMessage)
            ->subject('RiseFlow Platform Digest (' . $this->periodLabel . ') - ' . now()->format('Y-m-d'))
            ->greeting('Platform digest (' . $this->periodLabel . ')')
            ->line('Schools: total ' . number_format((int) $school['total']) . ', active ' . number_format((int) $school['active']) . ', trial ' . number_format((int) $school['trial']) . ', suspended ' . number_format((int) $school['suspended']) . '.')
            ->line('Growth: new schools in window ' . number_format((int) $school['new_in_window']) . ', new users in window ' . number_format((int) $users['new_in_window']) . '.')
            ->line('Users: students ' . number_format((int) $users['students']) . ', teachers ' . number_format((int) $users['teachers']) . '.')
            ->line('Billing: estimated billable students ' . number_format((int) $billing['estimated_billable_students']) . ', projected MRR ₦' . number_format((int) $billing['projected_mrr_ngn']) . '.')
            ->line('Risk/Health: at-risk schools ' . number_format((int) $billing['at_risk_schools']) . ', healthy ' . number_format((int) $health['healthy']) . ', watch ' . number_format((int) $health['watch']) . ', at risk ' . number_format((int) $health['at_risk']) . ', critical ' . number_format((int) $health['critical']) . ', average score ' . number_format((int) $health['average_score']) . '/100.')
            ->line('Affiliate: approved ' . number_format((int) $affiliate['approved']) . ', pending ' . number_format((int) $affiliate['pending']) . ', commission in window ₦' . number_format((int) $affiliate['commission_in_window_ngn']) . ', pending payouts ₦' . number_format((int) $affiliate['pending_payouts_ngn']) . '.')
            ->action('Open Platform Dashboard', route('platform.dashboard'))
            ->salutation('RiseFlow Platform Ops');
    }
}
