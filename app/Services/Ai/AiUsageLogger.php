<?php

namespace App\Services\Ai;

use App\Models\AiAuditLog;
use App\Models\AiRequest;
use App\Models\School;
use App\User;
use Illuminate\Support\Str;

class AiUsageLogger
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function createRequest(string $feature, array $messages, ?School $school, ?User $user, array $meta = []): AiRequest
    {
        return AiRequest::create([
            'school_id' => $school ? $school->id : null,
            'user_id' => $user ? $user->id : null,
            'feature' => $feature,
            'provider' => data_get($meta, 'provider'),
            'model' => data_get($meta, 'model'),
            'status' => 'queued',
            'prompt_hash' => hash('sha256', json_encode($this->redactedMessages($messages))),
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public function markSuccess(AiRequest $request, array $result, int $latencyMs): AiRequest
    {
        $request->update([
            'provider' => (string) data_get($result, 'provider'),
            'model' => (string) data_get($result, 'model'),
            'status' => 'success',
            'tokens_input' => (int) data_get($result, 'tokens_input', 0),
            'tokens_output' => (int) data_get($result, 'tokens_output', 0),
            'latency_ms' => $latencyMs,
            'error_code' => null,
            'error_message' => null,
        ]);

        $this->logEvent($request->id, 'success', [
            'provider' => data_get($result, 'provider'),
            'model' => data_get($result, 'model'),
            'fallback_from' => data_get($result, 'fallback_from'),
            'trace_id' => data_get($result, 'trace_id'),
        ]);

        return $request;
    }

    public function markFailed(AiRequest $request, \Throwable $e, int $latencyMs): AiRequest
    {
        $request->update([
            'status' => 'failed',
            'latency_ms' => $latencyMs,
            'error_code' => class_basename($e),
            'error_message' => Str::limit($e->getMessage(), 1000),
        ]);

        $this->logEvent($request->id, 'failed', [
            'exception' => class_basename($e),
            'message' => Str::limit($e->getMessage(), 300),
        ]);

        return $request;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function logEvent(int $aiRequestId, string $event, ?array $payload = null): void
    {
        AiAuditLog::create([
            'ai_request_id' => $aiRequestId,
            'event' => $event,
            'payload' => $payload,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private function redactedMessages(array $messages): array
    {
        return array_map(function (array $message): array {
            $content = (string) ($message['content'] ?? '');
            $content = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[redacted-email]', $content) ?: $content;
            $content = preg_replace('/\+?\d[\d\-\s()]{7,}\d/', '[redacted-phone]', $content) ?: $content;
            $message['content'] = $content;
            return $message;
        }, $messages);
    }
}
