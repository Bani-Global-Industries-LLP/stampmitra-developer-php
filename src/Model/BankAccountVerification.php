<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Bank account verification result (POST /v1/verify/bank-account).
 */
final class BankAccountVerification extends Model
{
    public function __construct(
        public readonly ?string $referenceId = null,
        public readonly ?bool $valid = null,
        public readonly ?string $nameAtBank = null,
        public readonly ?string $bankName = null,
        public readonly ?string $utr = null,
        public readonly ?string $city = null,
        public readonly ?string $branch = null,
        public readonly ?string $micr = null,
        public readonly ?string $nameMatchScore = null,
        public readonly ?string $nameMatchResult = null,
        public readonly ?string $accountStatus = null,
        public readonly ?string $accountStatusCode = null,
        public readonly ?IfscDetails $ifscDetails = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'referenceId'),
            self::bool($data, 'valid'),
            self::str($data, 'nameAtBank'),
            self::str($data, 'bankName'),
            self::str($data, 'utr'),
            self::str($data, 'city'),
            self::str($data, 'branch'),
            self::str($data, 'micr'),
            self::str($data, 'nameMatchScore'),
            self::str($data, 'nameMatchResult'),
            self::str($data, 'accountStatus'),
            self::str($data, 'accountStatusCode'),
            self::obj($data, 'ifscDetails', IfscDetails::class),
        ))->withRaw($data);
    }
}
