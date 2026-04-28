<?php

namespace App\Jobs;

use App\Models\PlatformNotification;
use App\Services\OutboundWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchPlatformWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    private string $event;
    private int $notificationId;

    public function __construct(string $event, int $notificationId)
    {
        $this->event = $event;
        $this->notificationId = $notificationId;
        $this->onQueue('webhooks');
    }

    public function handle(OutboundWebhookService $service): void
    {
        $notification = PlatformNotification::query()->find($this->notificationId);
        if (! $notification) {
            return;
        }

        $service->dispatch($this->event, $notification);
    }
}
