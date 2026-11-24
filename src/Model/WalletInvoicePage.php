<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * One page of wallet invoices.
 */
final class WalletInvoicePage extends Model
{
    public function __construct(
        /** @var list<WalletInvoice> */
        public readonly array $items = [],
        public readonly ?string $nextCursor = null,
        public readonly ?string $notice = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::list($data, 'items', WalletInvoice::class),
            self::str($data, 'nextCursor'),
            self::str($data, 'notice'),
        ))->withRaw($data);
    }
}
