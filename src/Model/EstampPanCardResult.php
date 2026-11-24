<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Answer (202) of POST /v1/estamp/stamps/{stampId}/pan-card.
 */
final class EstampPanCardResult extends Model
{
    public function __construct(
        public readonly ?string $stampId = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $panCard = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'stampId'),
            self::arr($data, 'panCard'),
        ))->withRaw($data);
    }
}
