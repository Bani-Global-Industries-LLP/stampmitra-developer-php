<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Passport verification result (POST /v1/verify/passport).
 */
final class PassportVerification extends Model
{
    public function __construct(
        public readonly ?string $referenceId = null,
        public readonly ?bool $valid = null,
        public readonly ?string $name = null,
        public readonly ?string $dob = null,
        public readonly ?string $applicationType = null,
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
            self::str($data, 'dob'),
            self::str($data, 'applicationType'),
        ))->withRaw($data);
    }
}
