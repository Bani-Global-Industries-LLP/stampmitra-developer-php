<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Balance of the Stamp Duty or Service Fee wallet (a decimal string, in rupees).
 */
final class WalletBalance extends Model
{
    public function __construct(
        public readonly ?string $balance = null,
        public readonly ?string $currency = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'balance'),
            self::str($data, 'currency'),
        ))->withRaw($data);
    }
}
