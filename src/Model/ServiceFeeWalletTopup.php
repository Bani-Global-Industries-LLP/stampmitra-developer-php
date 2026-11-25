<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Service wallet top-up (the class name is kept for compatibility; minimum Rs 1,000 plus 18% GST). Every amount is a string.
 */
final class ServiceFeeWalletTopup extends Model
{
    public function __construct(
        public readonly ?string $baseAmount = null,
        public readonly ?string $gstAmount = null,
        public readonly ?string $processingFeePercent = null,
        public readonly ?string $processingFeeAmount = null,
        public readonly ?string $creditAmount = null,
        public readonly ?string $totalCharged = null,
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
            self::str($data, 'baseAmount'),
            self::str($data, 'gstAmount'),
            self::str($data, 'processingFeePercent'),
            self::str($data, 'processingFeeAmount'),
            self::str($data, 'creditAmount'),
            self::str($data, 'totalCharged'),
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
