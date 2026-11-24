<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * A document (the payer's PAN card) attached to an order.
 */
final class EstampDocument extends Model
{
    public function __construct(
        public readonly ?string $documentId = null,
        public readonly ?string $orderId = null,
        public readonly ?string $type = null,
        public readonly ?string $fileName = null,
        public readonly ?string $mimeType = null,
        public readonly ?int $sizeBytes = null,
        public readonly ?string $receivedAt = null,
        public readonly ?string $status = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'documentId'),
            self::str($data, 'orderId'),
            self::str($data, 'type'),
            self::str($data, 'fileName'),
            self::str($data, 'mimeType'),
            self::int($data, 'sizeBytes'),
            self::str($data, 'receivedAt'),
            self::str($data, 'status'),
        ))->withRaw($data);
    }
}
