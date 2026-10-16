<?php

declare(strict_types=1);

namespace StampMitra\Exception;

/** HTTP 429: slow down. */
class RateLimitError extends StampMitraError
{
    /**
     * @param array<string, mixed>|string|null $body
     * @param array<string, list<string>>      $headers
     */
    public function __construct(
        string $message,
        ?int $status = 429,
        ?string $errorCode = null,
        ?string $requestId = null,
        array|string|null $body = null,
        ?string $field = null,
        array $headers = [],
        private readonly ?float $retryAfter = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $errorCode, $requestId, $body, $field, $headers, $previous);
    }

    /** Seconds to wait before retrying, from the Retry-After header. */
    public function getRetryAfter(): ?float
    {
        return $this->retryAfter;
    }
}
