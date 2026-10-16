<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Driving licence verification result (POST /v1/verify/driving-license).
 */
final class DrivingLicenseVerification extends Model
{
    public function __construct(
        public readonly ?string $referenceId = null,
        public readonly ?bool $valid = null,
        public readonly ?string $name = null,
        public readonly ?string $fatherOrHusbandName = null,
        public readonly ?string $dob = null,
        public readonly ?string $address = null,
        public readonly ?string $validUpto = null,
        public readonly ?string $dlStatus = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'referenceId'),
            self::bool($data, 'valid'),
            self::str($data, 'name'),
            self::str($data, 'fatherOrHusbandName'),
            self::str($data, 'dob'),
            self::str($data, 'address'),
            self::str($data, 'validUpto'),
            self::str($data, 'dlStatus'),
        ))->withRaw($data);
    }
}
