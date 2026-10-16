<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Voter ID verification result (POST /v1/verify/voter-id).
 */
final class VoterIdVerification extends Model
{
    public function __construct(
        public readonly ?string $referenceId = null,
        public readonly ?bool $valid = null,
        public readonly ?string $name = null,
        public readonly ?string $dob = null,
        public readonly ?string $gender = null,
        public readonly ?int $age = null,
        public readonly ?string $address = null,
        public readonly ?string $state = null,
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
            self::str($data, 'gender'),
            self::int($data, 'age'),
            self::str($data, 'address'),
            self::str($data, 'state'),
        ))->withRaw($data);
    }
}
