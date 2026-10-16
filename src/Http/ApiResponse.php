<?php

declare(strict_types=1);

namespace StampMitra\Http;

/**
 * Response metadata, available from every model via `$model->response()`.
 */
final class ApiResponse
{
    /**
     * @param array<string, list<string>> $headers
     * @param array<string, mixed>        $data
     */
    public function __construct(
        public readonly int $status,
        public readonly array $headers,
        public readonly array $data,
        public readonly ?string $requestId,
        public readonly int $attempts = 1,
    ) {
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $values) {
            if (strcasecmp($key, $name) === 0) {
                return $values[0] ?? null;
            }
        }

        return null;
    }
}
