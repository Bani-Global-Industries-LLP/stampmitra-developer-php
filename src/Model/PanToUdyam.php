<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * PAN to Udyam lookup result (POST /v1/verify/pan-to-udyam).
 */
final class PanToUdyam extends Model
{
    public function __construct(
        public readonly ?string $referenceId = null,
        public readonly ?bool $found = null,
        public readonly ?string $status = null,
        public readonly ?string $udyamNumber = null,
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
            self::str($data, 'status'),
            self::str($data, 'udyamNumber'),
        ))->withRaw($data);
    }
}
