<?php

namespace App\Services;

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

            $this->deliver($endpoint, $event, $payload, $notification->id);
        }
    }

    private function deliver(PlatformWebhookEndpoint $endpoint, string $event, array $payload, int $notificationId): void
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
            'attempt' => 1,
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
            ])->save();
        } catch (\Throwable $e) {
            $delivery->forceFill([
                'response_status' => null,
                'is_success' => false,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
                'delivered_at' => now(),
            ])->save();

            $endpoint->forceFill([
                'last_failure_at' => now(),
            ])->save();

            Log::warning('Outbound webhook delivery failed.', [
                'endpoint_id' => $endpoint->id,
                'event' => $event,
                'url' => $endpoint->url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
