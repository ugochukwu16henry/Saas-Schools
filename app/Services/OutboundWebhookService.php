<?php

namespace App\Services;

use App\Jobs\DeliverPlatformWebhookJob;
use App\Models\PlatformNotification;
use App\Models\PlatformWebhookDelivery;
use App\Models\PlatformWebhookEndpoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OutboundWebhookService
{
    public function dispatch(string $event, PlatformNotification $notification): void
    {
        $endpoints = PlatformWebhookEndpoint::query()
            ->where('is_active', true)
            ->get();

        $payload = [
            'notification_id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'school_id' => $notification->school_id,
            'payload' => $notification->payload ?? [],
            'created_at' => optional($notification->created_at)->toIso8601String(),
        ];

        foreach ($endpoints as $endpoint) {
            /** @var PlatformWebhookEndpoint $endpoint */
            if (! $endpoint->supportsEvent($event)) {
                continue;
            }

            DeliverPlatformWebhookJob::dispatch($endpoint->id, $event, $payload, $notification->id);
        }
    }

    public function deliver(PlatformWebhookEndpoint $endpoint, string $event, array $payload, int $notificationId, int $attempt = 1): void
    {
        $deliveryRequestId = (string) Str::uuid();
        $body = [
            'id' => $deliveryRequestId,
            'event' => $event,
            'occurred_at' => now()->toIso8601String(),
            'payload' => $payload,
        ];

        $json = json_encode($body);
        $signature = hash_hmac('sha256', (string) $json, $endpoint->secret);

        $delivery = PlatformWebhookDelivery::create([
            'endpoint_id' => $endpoint->id,
            'event_type' => $event,
            'platform_notification_id' => $notificationId,
            'request_id' => $deliveryRequestId,
            'attempt' => $attempt,
        ]);

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->withHeaders([
                    'X-RiseFlow-Event' => $event,
                    'X-RiseFlow-Delivery' => $deliveryRequestId,
                    'X-RiseFlow-Signature' => $signature,
                ])
                ->post($endpoint->url, $body);

            $delivery->forceFill([
                'response_status' => $response->status(),
                'is_success' => $response->successful(),
                'error_message' => $response->successful() ? null : mb_substr((string) $response->body(), 0, 1000),
                'delivered_at' => now(),
            ])->save();

            $endpoint->forceFill([
                'last_success_at' => $response->successful() ? now() : $endpoint->last_success_at,
                'last_failure_at' => $response->successful() ? $endpoint->last_failure_at : now(),
                'consecutive_failures' => $response->successful() ? 0 : ((int) $endpoint->consecutive_failures + 1),
            ])->save();

            Log::info('automation.metrics.outbound_webhook', [
                'endpoint_id' => $endpoint->id,
                'event' => $event,
                'attempt' => $attempt,
                'success' => $response->successful(),
                'status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            $delivery->forceFill([
                'response_status' => null,
                'is_success' => false,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
                'delivered_at' => now(),
            ])->save();

            $endpoint->forceFill([
                'last_failure_at' => now(),
                'consecutive_failures' => (int) $endpoint->consecutive_failures + 1,
                'is_active' => ((int) $endpoint->consecutive_failures + 1) >= (int) config('platform.webhooks.auto_disable_after_failures', 10) ? false : $endpoint->is_active,
            ])->save();

            Log::warning('Outbound webhook delivery failed.', [
                'endpoint_id' => $endpoint->id,
                'event' => $event,
                'url' => $endpoint->url,
                'attempt' => $attempt,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
