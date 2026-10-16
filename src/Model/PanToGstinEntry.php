<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * One GSTIN registered against a PAN.
 */
final class PanToGstinEntry extends Model
{
    public function __construct(
        public readonly ?string $gstin = null,
        public readonly ?string $state = null,
        public readonly ?string $status = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'gstin'),
            self::str($data, 'state'),
            self::str($data, 'status'),
        ))->withRaw($data);
    }
}
