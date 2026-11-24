<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * One page of GET /v1/estamp/orders.
 */
final class EstampOrderList extends Model
{
    public function __construct(
        /** @var list<EstampOrder> */
        public readonly array $items = [],
        public readonly ?string $nextCursor = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::list($data, 'items', EstampOrder::class),
            self::str($data, 'nextCursor'),
        ))->withRaw($data);
    }
}
