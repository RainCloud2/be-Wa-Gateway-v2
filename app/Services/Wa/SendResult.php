<?php

namespace App\Services\Wa;

/**
 * Outcome of a send attempt through a WhatsApp driver.
 */
class SendResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?int $statusCode = null,
        public readonly ?string $error = null,
        public readonly bool $retryable = false,
    ) {}

    public static function success(int $statusCode = 202): self
    {
        return new self(true, $statusCode);
    }

    public static function failure(?string $error = null, ?int $statusCode = null, bool $retryable = true): self
    {
        return new self(false, $statusCode, $error, $retryable);
    }
}
