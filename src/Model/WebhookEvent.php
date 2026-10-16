<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * A webhook delivery payload (see Webhook::constructEvent).
 */
final class WebhookEvent extends Model
{
    public function __construct(
        public readonly ?string $coreOrderId = null,
        public readonly ?string $coreEventType = null,
        public readonly ?string $occurredAt = null,
        public readonly ?string $orderId = null,
        public readonly ?string $externalOrderId = null,
        public readonly ?string $status = null,
        public readonly ?string $eventId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'coreOrderId'),
            self::str($data, 'coreEventType'),
            self::str($data, 'occurredAt'),
            self::str($data, 'orderId'),
            self::str($data, 'externalOrderId'),
            self::str($data, 'status'),
            self::str($data, 'eventId'),
        ))->withRaw($data);
    }
}
