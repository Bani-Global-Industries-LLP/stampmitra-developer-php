<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * One API request log entry.
 */
final class LogEntry extends Model
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $actorType = null,
        public readonly ?string $actorId = null,
        public readonly ?string $service = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $metadata = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'id'),
            self::str($data, 'createdAt'),
            self::str($data, 'actorType'),
            self::str($data, 'actorId'),
            self::str($data, 'service'),
            self::arr($data, 'metadata'),
        ))->withRaw($data);
    }
}
