<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * PAN to GSTIN lookup result (POST /v1/verify/pan-to-gstin).
 */
final class PanToGstin extends Model
{
    public function __construct(
        public readonly ?string $referenceId = null,
        public readonly ?bool $found = null,
        /** @var list<PanToGstinEntry> */
        public readonly array $gstins = [],
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
            self::list($data, 'gstins', PanToGstinEntry::class),
        ))->withRaw($data);
    }
}
