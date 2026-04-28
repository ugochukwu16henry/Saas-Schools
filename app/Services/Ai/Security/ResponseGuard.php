<?php

namespace App\Services\Ai\Security;

class ResponseGuard
{
    public function guard(string $content): string
    {
        $blocked = ['<script', 'javascript:', 'data:text/html'];
        $output = strip_tags($content);

        foreach ($blocked as $needle) {
            $output = str_ireplace($needle, '[removed]', $output);
        }

        $output = preg_replace('/\s+/', ' ', $output) ?: $output;

        return mb_substr(trim($output), 0, (int) config('ai.guard.max_output_chars', 4000));
    }
}
