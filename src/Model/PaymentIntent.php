<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * A payment intent (POST/GET /v1/payments/intents).
 */
final class PaymentIntent extends Model
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $externalOrderId = null,
        public readonly ?string $coreOrderId = null,
        public readonly ?int $amountMinor = null,
        public readonly ?string $currency = null,
        public readonly ?string $status = null,
        public readonly ?string $providerIntentId = null,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'id'),
            self::str($data, 'externalOrderId'),
            self::str($data, 'coreOrderId'),
            self::int($data, 'amountMinor'),
            self::str($data, 'currency'),
            self::str($data, 'status'),
            self::str($data, 'providerIntentId'),
            self::str($data, 'createdAt'),
        ))->withRaw($data);
    }
}
