<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Result of GET /v1/orders/{id}/payment.
 */
final class OrderPaymentResponse extends Model
{
    public function __construct(
        public readonly ?string $externalOrderId = null,
        public readonly mixed $payment = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'externalOrderId'),
            $data['payment'] ?? null,
        ))->withRaw($data);
    }
}
