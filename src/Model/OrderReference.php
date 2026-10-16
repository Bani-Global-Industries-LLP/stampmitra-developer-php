<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Result of POST /v1/orders.
 */
final class OrderReference extends Model
{
    public function __construct(
        public readonly ?string $externalOrderId = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $serviceCode = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'externalOrderId'),
            self::str($data, 'createdAt'),
            self::str($data, 'serviceCode'),
        ))->withRaw($data);
    }
}
