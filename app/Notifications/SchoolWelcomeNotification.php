<?php

namespace App\Notifications;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SchoolWelcomeNotification extends Notification
{
    use Queueable;

    private School $school;

    public function __construct(School $school)
    {
        $this->school = $school;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to RiseFlow — ' . $this->school->name . ' is live!')
            ->greeting('Welcome, ' . ($notifiable->name ?? 'there') . '!')
            ->line('Your school **' . $this->school->name . '** has been registered successfully on RiseFlow.')
            ->line('Here\'s how to get started:')
            ->line('1. Log in and complete your school profile (logo, address, contact details)')
            ->line('2. Set up your classes and subjects')
            ->line('3. Invite your teachers and admit your students')
            ->line('Your first **50 students are completely free** — no billing until you exceed that.')
            ->action('Log In Now', route('login'))
            ->line('Need help? Simply reply to this email and we\'ll be happy to assist.')
            ->salutation('The RiseFlow Team');
    }
}
