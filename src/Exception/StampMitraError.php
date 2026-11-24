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

    /**
     * error.details of the response envelope (empty when absent).
     *
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        $error = is_array($this->body) && isset($this->body['error']) && is_array($this->body['error']) ? $this->body['error'] : [];
        $details = $error['details'] ?? null;
        if (!is_array($details)) {
            return [];
        }
        $out = [];
        foreach ($details as $k => $v) {
            $out[(string) $k] = $v;
        }

        return $out;
    }

    /**
     * error.details.errors[]: the per-field findings of a rejected e-Stamp order body.
     *
     * @return list<FieldError>
     */
    public function getFieldErrors(): array
    {
        $errors = $this->getDetails()['errors'] ?? null;
        $out = [];
        if (is_array($errors)) {
            foreach ($errors as $item) {
                if (is_array($item)) {
                    $out[] = FieldError::fromArray($item);
                }
            }
        }

        return $out;
    }

    /** error.details.wallet on a 402: "STAMP_DUTY" or "SERVICE_FEE" (null when not given). */
    public function getWallet(): ?string
    {
        $wallet = $this->getDetails()['wallet'] ?? null;

        return $wallet === 'STAMP_DUTY' || $wallet === 'SERVICE_FEE' ? $wallet : null;
    }

    /** Seconds from a numeric Retry-After response header, when present. */
    public function getRetryAfter(): ?float
    {
        foreach ($this->headers as $name => $values) {
            if (strcasecmp((string) $name, 'retry-after') === 0 && isset($values[0]) && is_numeric($values[0])) {
                return max(0.0, (float) $values[0]);
            }
        }

        return null;
    }
}
