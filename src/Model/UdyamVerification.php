<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Udyam verification result (POST /v1/verify/udyam).
 */
final class UdyamVerification extends Model
{
    public function __construct(
        public readonly ?string $referenceId = null,
        public readonly ?bool $found = null,
        public readonly ?string $udyamStatus = null,
        public readonly ?string $enterpriseName = null,
        public readonly ?string $enterpriseType = null,
        public readonly ?string $organizationType = null,
        public readonly ?string $certificateUrl = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'referenceId'),
            self::bool($data, 'found'),
            self::str($data, 'udyamStatus'),
            self::str($data, 'enterpriseName'),
            self::str($data, 'enterpriseType'),
            self::str($data, 'organizationType'),
            self::str($data, 'certificateUrl'),
        ))->withRaw($data);
    }
}
