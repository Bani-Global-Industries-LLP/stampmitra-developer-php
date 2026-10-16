<?php

declare(strict_types=1);

namespace StampMitra\Exception;

/**
 * Base class of every error raised by the SDK.
 *
 * For API errors the HTTP status, the machine readable error code, the
 * server-side request id and the decoded response body are available through
 * the getters. Errors raised locally (client-side validation, network
 * failures) carry a null status.
 */
class StampMitraError extends \RuntimeException
{
    /**
     * @param array<string, mixed>|string|null $body Decoded JSON body, raw text for non-JSON bodies, or null
     * @param array<string, list<string>>      $headers Response headers (empty for local errors)
     */
    public function __construct(
        string $message,
        private readonly ?int $status = null,
        private readonly ?string $errorCode = null,
        private readonly ?string $requestId = null,
        private readonly array|string|null $body = null,
        private readonly ?string $field = null,
        private readonly array $headers = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status ?? 0, $previous);
    }

    /** HTTP status code, or null when the error did not come from a response. */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /** Machine readable error code from the response, e.g. "VALIDATION_ERROR". */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /** Server-side request id. Quote it when contacting support. */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /** The offending input field for validation errors, when known. */
    public function getField(): ?string
    {
        return $this->field;
    }

    /**
     * @return array<string, mixed>|string|null
     */
    public function getBody(): array|string|null
    {
        return $this->body;
    }

    /**
     * @return array<string, list<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
