<?php

namespace App\Services\Ai\Contracts;

interface AiClientInterface
{
    /**
     * @param  array<int, array<string, string>>  $messages
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function generate(array $messages, array $options = []): array;
}
