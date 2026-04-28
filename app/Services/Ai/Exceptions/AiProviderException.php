<?php

namespace App\Services\Ai\Exceptions;

use RuntimeException;

class AiProviderException extends RuntimeException
{
    private string $provider;
    private int $statusCode;
    private string $errorType;
    private bool $retryable;

    public function __construct(
        string $message,
        string $provider,
        int $statusCode = 0,
        string $errorType = 'provider_error',
        bool $retryable = true
    ) {
        parent::__construct($message, $statusCode);
        $this->provider = $provider;
        $this->statusCode = $statusCode;
        $this->errorType = $errorType;
        $this->retryable = $retryable;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function errorType(): string
    {
        return $this->errorType;
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }
}
