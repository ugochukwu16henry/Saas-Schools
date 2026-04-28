<?php

namespace App\Notifications;

use App\Models\StudentTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentTransferRequestedNotification extends Notification
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
        $fromSchool = optional($this->transfer->fromSchool)->name ?: 'another school';
        $toSchool = optional($this->transfer->toSchool)->name ?: 'your school';

        return (new MailMessage)
            ->subject('New student transfer request received')
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line('A student transfer request has been initiated to your school.')
            ->line('Student: ' . $studentName)
            ->line('From School: ' . $fromSchool)
            ->line('To School: ' . $toSchool)
            ->line('Requested At: ' . optional($this->transfer->created_at)->toDateTimeString())
            ->line('Please review and accept/reject this transfer from your transfer inbox.')
            ->action('Open Transfer Inbox', route('transfers.inbox'));
    }
}
