<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * An e-Stamp order (create, get, list, cancel).
 */
final class EstampOrder extends Model
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $orderId = null,
        public readonly ?string $reference = null,
        public readonly ?string $state = null,
        public readonly ?string $status = null,
        public readonly ?bool $isTest = null,
        public readonly ?int $stampCount = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $stamps = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $counts = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $documents = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $advisories = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $errors = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $warnings = null,
        public readonly ?bool $warningsAcknowledged = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'id'),
            self::str($data, 'orderId'),
            self::str($data, 'reference'),
            self::str($data, 'state'),
            self::str($data, 'status'),
            self::bool($data, 'isTest'),
            self::int($data, 'stampCount'),
            self::arr($data, 'stamps'),
            self::arr($data, 'counts'),
            self::arr($data, 'documents'),
            self::arr($data, 'advisories'),
            self::arr($data, 'errors'),
            self::arr($data, 'warnings'),
            self::bool($data, 'warningsAcknowledged'),
            self::str($data, 'createdAt'),
            self::str($data, 'updatedAt'),
        ))->withRaw($data);
    }
}
