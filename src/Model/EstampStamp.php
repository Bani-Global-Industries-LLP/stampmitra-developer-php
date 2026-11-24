<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * One e-Stamp of an order.
 */
final class EstampStamp extends Model
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $clientReference = null,
        public readonly ?string $bulkOrderId = null,
        public readonly ?string $status = null,
        public readonly ?bool $verified = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $panCard = null,
        public readonly ?string $state = null,
        public readonly ?string $category = null,
        public readonly ?float $amount = null,
        public readonly ?string $uin = null,
        public readonly ?string $costCentre = null,
        public readonly ?string $businessUnit = null,
        public readonly ?string $purpose = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'id'),
            self::str($data, 'clientReference'),
            self::str($data, 'bulkOrderId'),
            self::str($data, 'status'),
            self::bool($data, 'verified'),
            self::arr($data, 'panCard'),
            self::str($data, 'state'),
            self::str($data, 'category'),
            self::num($data, 'amount'),
            self::str($data, 'uin'),
            self::str($data, 'costCentre'),
            self::str($data, 'businessUnit'),
            self::str($data, 'purpose'),
        ))->withRaw($data);
    }
}
