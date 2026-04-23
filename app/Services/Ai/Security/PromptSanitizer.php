<?php

namespace App\Services\Ai\Security;

class PromptSanitizer
{
    public function sanitize(string $text): string
    {
        $clean = strip_tags($text);
        $clean = preg_replace('/\s+/', ' ', $clean) ?: $clean;

        return trim($clean);
    }
}
