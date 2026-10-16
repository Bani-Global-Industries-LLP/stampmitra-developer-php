<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * GSTIN verification result (POST /v1/verify/gstin).
 */
final class GstinVerification extends Model
{
    public function __construct(
        public readonly ?string $referenceId = null,
        public readonly ?string $gstin = null,
        public readonly ?bool $valid = null,
        public readonly ?string $legalName = null,
        public readonly ?string $tradeName = null,
        public readonly ?string $registrationStatus = null,
        public readonly ?string $principalPlaceOfBusiness = null,
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
            self::str($data, 'GSTIN', 'gstin'),
            self::bool($data, 'valid'),
            self::str($data, 'legalName'),
            self::str($data, 'tradeName'),
            self::str($data, 'registrationStatus'),
            self::str($data, 'principalPlaceOfBusiness'),
            self::str($data, 'message'),
        ))->withRaw($data);
    }
}
