<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Stamps of an order.
 */
final class EstampStampList extends Model
{
    public function __construct(
        /** @var list<EstampStamp> */
        public readonly array $items = [],
        public readonly ?int $count = null,
        public readonly ?string $nextCursor = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::list($data, 'items', EstampStamp::class),
            self::int($data, 'count'),
            self::str($data, 'nextCursor'),
        ))->withRaw($data);
    }
}
