<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Both wallets in one call.
 */
final class EstampWallet extends Model
{
    public function __construct(
        public readonly ?string $balance = null,
        public readonly ?string $held = null,
        public readonly ?string $available = null,
        public readonly ?string $currency = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $serviceFee = null,
        public readonly ?string $mode = null,
        public readonly ?bool $simulated = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'balance'),
            self::str($data, 'held'),
            self::str($data, 'available'),
            self::str($data, 'currency'),
            self::arr($data, 'serviceFee'),
            self::str($data, 'mode'),
            self::bool($data, 'simulated'),
        ))->withRaw($data);
    }
}
