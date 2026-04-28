<?php

namespace App\Services\Ai;

class StructuredOutput
{
    /**
     * @return array<string, mixed>|null
     */
    public function decodeObject(string $content): ?array
    {
        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @param  array<int, string>  $requiredKeys
     */
    public function hasRequiredKeys(array $decoded, array $requiredKeys): bool
    {
        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $decoded)) {
                return false;
            }
        }

        return true;
    }
}
