<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * What a state needs for an order line. `exampleBody` is a ready-to-edit order body.
 */
final class EstampRequirements extends Model
{
    public function __construct(
        /** @var array<array-key, mixed>|null */
        public readonly ?array $state = null,
        public readonly ?string $category = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $article = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $denomination = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $fields = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $limits = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $meta = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $exampleBody = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::arr($data, 'state'),
            self::str($data, 'category'),
            self::arr($data, 'article'),
            self::arr($data, 'denomination'),
            self::arr($data, 'fields'),
            self::arr($data, 'limits'),
            self::arr($data, 'meta'),
            self::arr($data, 'exampleBody'),
        ))->withRaw($data);
    }
}
