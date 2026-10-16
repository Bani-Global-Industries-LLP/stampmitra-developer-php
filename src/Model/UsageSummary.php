<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Result of GET /v1/usage/summary.
 */
final class UsageSummary extends Model
{
    public function __construct(
        /** @var array<array-key, mixed>|null */
        public readonly ?array $currentPeriod = null,
        public readonly ?int $totalApiRequests = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $callsByService = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $callsByPeriod = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $callsByDay = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::arr($data, 'currentPeriod'),
            self::int($data, 'totalApiRequests'),
            self::arr($data, 'callsByService'),
            self::arr($data, 'callsByPeriod'),
            self::arr($data, 'callsByDay'),
        ))->withRaw($data);
    }
}
