<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Certificate delivery status of a stamp. `available` is the discriminator; downloadUrl is short-lived.
 */
final class EstampCertificate extends Model
{
    public function __construct(
        public readonly ?bool $available = null,
        public readonly ?string $status = null,
        public readonly ?string $downloadUrl = null,
        public readonly ?string $expiresAt = null,
        public readonly ?string $certificateNumber = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::bool($data, 'available'),
            self::str($data, 'status'),
            self::str($data, 'downloadUrl'),
            self::str($data, 'expiresAt'),
            self::str($data, 'certificateNumber'),
        ))->withRaw($data);
    }
}
