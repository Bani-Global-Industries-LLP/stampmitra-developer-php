<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * A director listed on a CIN verification.
 */
final class CinDirector extends Model
{
    public function __construct(
        public readonly ?string $din = null,
        public readonly ?string $name = null,
        public readonly ?string $designation = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'din'),
            self::str($data, 'name'),
            self::str($data, 'designation'),
        ))->withRaw($data);
    }
}
