<?php

namespace App\Services\Ai\Security;

class ResponseGuard
{
    public function guard(string $content): string
    {
        $blocked = ['<script', 'javascript:', 'data:text/html'];
        $output = $content;

        foreach ($blocked as $needle) {
            $output = str_ireplace($needle, '[removed]', $output);
        }

        return trim($output);
    }
}
