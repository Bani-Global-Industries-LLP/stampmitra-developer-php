<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Result of POST /v1/estamp/orders/validate (a dry run: nothing is created or billed).
 */
final class EstampValidateResult extends Model
{
    public function __construct(
        public readonly ?bool $valid = null,
        public readonly ?string $reference = null,
        public readonly ?string $state = null,
        public readonly ?string $status = null,
        public readonly ?bool $isTest = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $counts = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $errors = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $warnings = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $quote = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $documentsRequired = null,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::bool($data, 'valid'),
            self::str($data, 'reference'),
            self::str($data, 'state'),
            self::str($data, 'status'),
            self::bool($data, 'isTest'),
            self::arr($data, 'counts'),
            self::arr($data, 'errors'),
            self::arr($data, 'warnings'),
            self::arr($data, 'quote'),
            self::arr($data, 'documentsRequired'),
            self::str($data, 'createdAt'),
        ))->withRaw($data);
    }
}
