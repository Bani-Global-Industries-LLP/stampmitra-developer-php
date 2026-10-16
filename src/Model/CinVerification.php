<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * CIN verification result (POST /v1/verify/cin).
 */
final class CinVerification extends Model
{
    public function __construct(
        public readonly ?string $referenceId = null,
        public readonly ?bool $valid = null,
        public readonly ?string $companyName = null,
        public readonly ?string $incorporationDate = null,
        public readonly ?string $cinStatus = null,
        public readonly ?string $companyStatus = null,
        /** @var list<CinDirector> */
        public readonly array $directors = [],
        public readonly ?string $email = null,
        public readonly ?string $registrationNumber = null,
        public readonly ?string $incorporationCountry = null,
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
            self::str($data, 'companyName'),
            self::str($data, 'incorporationDate'),
            self::str($data, 'cinStatus'),
            self::str($data, 'companyStatus'),
            self::list($data, 'directors', CinDirector::class),
            self::str($data, 'email'),
            self::str($data, 'registrationNumber'),
            self::str($data, 'incorporationCountry'),
        ))->withRaw($data);
    }
}
