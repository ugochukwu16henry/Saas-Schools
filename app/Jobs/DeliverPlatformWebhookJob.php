<?php

namespace App\Jobs;

use App\Models\PlatformWebhookEndpoint;
use App\Services\OutboundWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeliverPlatformWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [15, 60, 180, 600];

    private int $endpointId;
    private string $event;
    private array $payload;
    private int $notificationId;
    private int $attempt;

    public function __construct(int $endpointId, string $event, array $payload, int $notificationId, int $attempt = 1)
    {
        $this->endpointId = $endpointId;
        $this->event = $event;
        $this->payload = $payload;
        $this->notificationId = $notificationId;
        $this->attempt = $attempt;
        $this->onQueue('webhooks');
    }

    public function handle(OutboundWebhookService $service): void
    {
        $endpoint = PlatformWebhookEndpoint::query()->find($this->endpointId);
        if (! $endpoint || ! $endpoint->is_active) {
            return;
        }

        $service->deliver($endpoint, $this->event, $this->payload, $this->notificationId, $this->attempt);
    }
}
