<?php

namespace App\Listeners;

use App\Models\TransferNotificationEvent;
use App\Notifications\StudentTransferAcceptedNotification;
use App\Notifications\StudentTransferRejectedNotification;
use App\Notifications\StudentTransferRequestedNotification;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;

class LogTransferNotificationFailure
{
    public function handleFailed(NotificationFailed $event): void
    {
        if (!$this->isTransferNotification($event->notification)) {
            return;
        }

        TransferNotificationEvent::query()->create([
            'transfer_id' => $this->transferId($event->notification),
            'school_id' => (int) ($event->notifiable->school_id ?? 0) ?: null,
            'notifiable_id' => (int) ($event->notifiable->id ?? 0) ?: null,
            'notifiable_email' => (string) ($event->notifiable->email ?? ''),
            'notification_class' => get_class($event->notification),
            'channel' => (string) $event->channel,
            'status' => 'failed',
            'error' => $this->extractError($event->data),
            'payload' => is_array($event->data) ? $event->data : null,
        ]);
    }

    public function handleSent(NotificationSent $event): void
    {
        if (!$this->isTransferNotification($event->notification)) {
            return;
        }

        TransferNotificationEvent::query()
            ->where('status', 'failed')
            ->whereNull('resolved_at')
            ->where('notification_class', get_class($event->notification))
            ->where('transfer_id', $this->transferId($event->notification))
            ->where('notifiable_id', (int) ($event->notifiable->id ?? 0))
            ->update(['resolved_at' => now()]);
    }

    private function isTransferNotification($notification): bool
    {
        return $notification instanceof StudentTransferRequestedNotification
            || $notification instanceof StudentTransferAcceptedNotification
            || $notification instanceof StudentTransferRejectedNotification;
    }

    private function transferId($notification): ?int
    {
        if (method_exists($notification, 'transferId')) {
            return (int) $notification->transferId();
        }

        return null;
    }

    private function extractError(?array $data): ?string
    {
        if (!$data) {
            return null;
        }

        $keys = ['message', 'error', 'exception', 'response'];
        foreach ($keys as $key) {
            if (!empty($data[$key])) {
                return is_scalar($data[$key]) ? (string) $data[$key] : json_encode($data[$key]);
            }
        }

        return null;
    }
}
