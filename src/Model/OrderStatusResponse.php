<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Result of GET /v1/orders/{id}/status.
 */
final class OrderStatusResponse extends Model
{
    public function __construct(
        public readonly ?string $externalOrderId = null,
        public readonly mixed $status = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'externalOrderId'),
            $data['status'] ?? null,
        ))->withRaw($data);
    }
}
