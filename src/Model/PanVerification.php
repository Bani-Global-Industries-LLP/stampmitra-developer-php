<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * PAN verification result (POST /v1/verify/pan).
 */
final class PanVerification extends Model
{
    public function __construct(
        public readonly ?string $referenceId = null,
        public readonly ?string $pan = null,
        public readonly ?string $type = null,
        public readonly ?bool $valid = null,
        public readonly ?string $registeredName = null,
        public readonly ?string $fatherName = null,
        public readonly ?string $panStatus = null,
        public readonly ?string $panStatusCode = null,
        public readonly ?string $panStatusDescription = null,
        public readonly ?string $nameMatchResult = null,
        public readonly ?string $nameMatchScore = null,
        public readonly ?string $aadhaarSeedingStatus = null,
        public readonly ?string $aadhaarSeedingStatusDescription = null,
        public readonly ?string $lastUpdatedAt = null,
        public readonly ?string $message = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'referenceId'),
            self::str($data, 'pan'),
            self::str($data, 'type'),
            self::bool($data, 'valid'),
            self::str($data, 'registeredName'),
            self::str($data, 'fatherName'),
            self::str($data, 'panStatus'),
            self::str($data, 'panStatusCode'),
            self::str($data, 'panStatusDescription'),
            self::str($data, 'nameMatchResult'),
            self::str($data, 'nameMatchScore'),
            self::str($data, 'aadhaarSeedingStatus'),
            self::str($data, 'aadhaarSeedingStatusDescription'),
            self::str($data, 'lastUpdatedAt'),
            self::str($data, 'message'),
        ))->withRaw($data);
    }
}
