<?php

namespace App\Services\Ai\PromptBuilders;

use App\Services\Ai\Security\PromptSanitizer;

class OpsSummaryPromptBuilder
{
    private PromptSanitizer $sanitizer;

    public function __construct(PromptSanitizer $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, string>>
     */
    public function build(array $payload): array
    {
        $title = $this->sanitizer->sanitize((string) ($payload['title'] ?? 'Operational update'));
        $notes = $this->sanitizer->sanitize((string) ($payload['notes'] ?? ''));

        return [
            [
                'role' => 'system',
                'content' => 'You are a school operations copilot. Return JSON only with keys: summary, risks, next_steps.',
            ],
            [
                'role' => 'user',
                'content' => "Summarize this operations update.\nTitle: {$title}\nNotes: {$notes}\nReturn JSON only: {\"summary\":\"...\",\"risks\":[\"...\"],\"next_steps\":[\"...\"]}",
            ],
        ];
    }
}
