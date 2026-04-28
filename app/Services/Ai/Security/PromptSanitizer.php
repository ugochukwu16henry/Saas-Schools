<?php

namespace App\Services\Ai\Security;

class PromptSanitizer
{
    public function sanitize(string $text): string
    {
        $clean = strip_tags($text);
        $clean = preg_replace('/\s+/', ' ', $clean) ?: $clean;
        $clean = $this->redactSensitiveData($clean);
        $clean = $this->neutralizePromptInjectionHints($clean);

        return trim($clean);
    }

    private function redactSensitiveData(string $text): string
    {
        $text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[redacted-email]', $text) ?: $text;
        $text = preg_replace('/\+?\d[\d\-\s()]{7,}\d/', '[redacted-phone]', $text) ?: $text;

        return $text;
    }

    private function neutralizePromptInjectionHints(string $text): string
    {
        $patterns = [
            '/ignore\s+previous\s+instructions/i',
            '/disregard\s+all\s+prior/i',
            '/reveal\s+(system|hidden)\s+prompt/i',
            '/act\s+as\s+root/i',
        ];

        return preg_replace($patterns, '[removed-instruction]', $text) ?: $text;
    }
}
