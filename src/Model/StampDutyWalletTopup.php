<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Stamp duty wallet top-up. baseAmount, gstAmount and totalCharged are numbers; the other amounts are strings. Show these figures to your payer; the SDK never computes fees.
 */
final class StampDutyWalletTopup extends Model
{
    public function __construct(
        public readonly ?float $baseAmount = null,
        public readonly ?string $processingFeePercent = null,
        public readonly ?string $processingFeeAmount = null,
        public readonly ?float $gstAmount = null,
        public readonly ?float $totalCharged = null,
        public readonly ?string $creditAmount = null,
        public readonly ?string $topupId = null,
        public readonly ?string $razorpayOrderId = null,
        public readonly ?string $razorpayKeyId = null,
        public readonly ?bool $sandbox = null,
        public readonly ?string $credited = null,
        public readonly ?string $balance = null,
        public readonly ?string $currency = null,
        public readonly ?string $notice = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::num($data, 'baseAmount'),
            self::str($data, 'processingFeePercent'),
            self::str($data, 'processingFeeAmount'),
            self::num($data, 'gstAmount'),
            self::num($data, 'totalCharged'),
            self::str($data, 'creditAmount'),
            self::str($data, 'topupId'),
            self::str($data, 'razorpayOrderId'),
            self::str($data, 'razorpayKeyId'),
            self::bool($data, 'sandbox'),
            self::str($data, 'credited'),
            self::str($data, 'balance'),
            self::str($data, 'currency'),
            self::str($data, 'notice'),
        ))->withRaw($data);
    }
}
