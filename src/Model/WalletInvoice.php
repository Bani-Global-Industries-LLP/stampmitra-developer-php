<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * A GST tax invoice of a wallet top-up (for the fee on Stamp Duty top-ups). creditedAmount is present on Stamp Duty invoices.
 */
final class WalletInvoice extends Model
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $invoiceNumber = null,
        public readonly ?string $razorpayOrderId = null,
        public readonly ?string $baseAmount = null,
        public readonly ?string $gstAmount = null,
        public readonly ?string $totalCharged = null,
        public readonly ?string $creditedAmount = null,
        public readonly ?string $gstin = null,
        public readonly ?bool $gstinVerified = null,
        public readonly ?string $legalBusinessName = null,
        public readonly ?string $status = null,
        public readonly ?string $paidAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'id'),
            self::str($data, 'invoiceNumber'),
            self::str($data, 'razorpayOrderId'),
            self::str($data, 'baseAmount'),
            self::str($data, 'gstAmount'),
            self::str($data, 'totalCharged'),
            self::str($data, 'creditedAmount'),
            self::str($data, 'gstin'),
            self::bool($data, 'gstinVerified'),
            self::str($data, 'legalBusinessName'),
            self::str($data, 'status'),
            self::str($data, 'paidAt'),
        ))->withRaw($data);
    }
}
