<?php

namespace App\Notifications;

use App\Models\StudentTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentTransferRejectedNotification extends Notification
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
        $fromSchool = optional($this->transfer->fromSchool)->name ?: 'your school';
        $toSchool = optional($this->transfer->toSchool)->name ?: 'requested school';

        return (new MailMessage)
            ->subject('Student transfer rejected')
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line('A student transfer request has been rejected.')
            ->line('Student: ' . $studentName)
            ->line('From School: ' . $fromSchool)
            ->line('Requested School: ' . $toSchool)
            ->line('Reason: ' . ($this->transfer->rejected_reason ?: 'No reason provided.'))
            ->action('View Transfer Outbox', route('transfers.outbox'));
    }
}
