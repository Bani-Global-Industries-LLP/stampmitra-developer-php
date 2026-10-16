<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Vehicle RC verification result (POST /v1/verify/rc).
 */
final class RcVerification extends Model
{
    public function __construct(
        public readonly ?string $referenceId = null,
        public readonly ?string $regNo = null,
        public readonly ?string $registrationStatus = null,
        public readonly ?string $ownerName = null,
        public readonly ?string $ownerFatherName = null,
        public readonly ?string $vehicleClass = null,
        public readonly ?string $vehicleManufacturerName = null,
        public readonly ?string $model = null,
        public readonly ?string $vehicleColor = null,
        public readonly ?string $chassis = null,
        public readonly ?string $engine = null,
        public readonly ?string $registrationDate = null,
        public readonly ?string $rcStatus = null,
        public readonly ?string $rcExpiryDate = null,
        public readonly ?string $insuranceUpto = null,
        public readonly ?bool $isCommercial = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'referenceId'),
            self::str($data, 'regNo'),
            self::str($data, 'registrationStatus'),
            self::str($data, 'ownerName'),
            self::str($data, 'ownerFatherName'),
            self::str($data, 'vehicleClass'),
            self::str($data, 'vehicleManufacturerName'),
            self::str($data, 'model'),
            self::str($data, 'vehicleColor'),
            self::str($data, 'chassis'),
            self::str($data, 'engine'),
            self::str($data, 'registrationDate'),
            self::str($data, 'rcStatus'),
            self::str($data, 'rcExpiryDate'),
            self::str($data, 'insuranceUpto'),
            self::bool($data, 'isCommercial'),
        ))->withRaw($data);
    }
}
