<?php

namespace App\Services\Ai\PromptBuilders;

use App\Services\Ai\Security\PromptSanitizer;

class AnnouncementPromptBuilder
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
        $audience = $this->sanitizer->sanitize((string) ($payload['audience'] ?? 'All school stakeholders'));
        $tone = $this->sanitizer->sanitize((string) ($payload['tone'] ?? 'professional'));
        $language = $this->sanitizer->sanitize((string) ($payload['language'] ?? 'English'));
        $context = $this->sanitizer->sanitize((string) ($payload['context'] ?? ''));
        $keyPoints = $this->sanitizer->sanitize((string) ($payload['key_points'] ?? ''));

        return [
            [
                'role' => 'system',
                'content' => 'You are an assistant for school administrators. Generate concise and clear communication drafts.',
            ],
            [
                'role' => 'user',
                'content' => "Create a school announcement.\nAudience: {$audience}\nTone: {$tone}\nLanguage: {$language}\nContext: {$context}\nKey points: {$keyPoints}\n\nReturn this format:\nTitle:\nBody:\nAction items:\nSMS version:",
            ],
        ];
    }
}
