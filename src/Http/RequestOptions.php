<?php

declare(strict_types=1);

namespace StampMitra\Http;

/**
 * Per-request overrides. Every SDK method accepts one (or an equivalent array).
 *
 * PHP has no cancellation token, so there is no signal option; use `timeout`.
 */
final class RequestOptions
{
    /**
     * @param array<string, string> $headers Extra headers for this request
     */
    public function __construct(
        public readonly ?float $timeout = null,
        public readonly ?int $maxRetries = null,
        public readonly ?string $idempotencyKey = null,
        public readonly array $headers = [],
    ) {
    }

    /**
     * @param self|array<string, mixed>|null $options
     */
    public static function from(self|array|null $options): self
    {
        if ($options instanceof self) {
            return $options;
        }
        $options ??= [];
        /** @var array<string, string> $headers */
        $headers = isset($options['headers']) && is_array($options['headers']) ? $options['headers'] : [];

        return new self(
            isset($options['timeout']) && is_numeric($options['timeout']) ? (float) $options['timeout'] : null,
            isset($options['maxRetries']) && is_numeric($options['maxRetries']) ? (int) $options['maxRetries'] : null,
            isset($options['idempotencyKey']) && is_string($options['idempotencyKey']) ? $options['idempotencyKey'] : null,
            $headers,
        );
    }
}
