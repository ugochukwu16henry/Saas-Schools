<?php

namespace App\Notifications;

use App\Models\StudentTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentTransferAcceptedNotification extends Notification
{
    use Queueable;

    private StudentTransfer $transfer;

    public function __construct(StudentTransfer $transfer)
    {
        $this->transfer = $transfer;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $studentName = optional($this->transfer->student)->name ?: 'student';
        $fromSchool = optional($this->transfer->fromSchool)->name ?: 'previous school';
        $toSchool = optional($this->transfer->toSchool)->name ?: 'new school';

        return (new MailMessage)
            ->subject('Student transfer accepted')
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line('A student transfer request has been accepted.')
            ->line('Student: ' . $studentName)
            ->line('From School: ' . $fromSchool)
            ->line('Current School: ' . $toSchool)
            ->line('Transferred At: ' . (optional($this->transfer->transferred_at)->toDateTimeString() ?: optional($this->transfer->updated_at)->toDateTimeString()))
            ->action('View Student Dashboard', route('dashboard'));
    }
}
