<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * CSV export of an order.
 */
final class EstampOrderExport extends Model
{
    public function __construct(
        public readonly ?string $filename = null,
        public readonly ?string $csv = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'filename'),
            self::str($data, 'csv'),
        ))->withRaw($data);
    }
}
