<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * IFSC branch details attached to a bank account verification.
 */
final class IfscDetails extends Model
{
    public function __construct(
        public readonly ?string $bank = null,
        public readonly ?string $ifsc = null,
        public readonly ?string $micr = null,
        public readonly ?string $nbin = null,
        public readonly ?string $address = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $branch = null,
        public readonly ?string $ifscSubcode = null,
        public readonly ?string $category = null,
        public readonly ?string $swiftCode = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'bank'),
            self::str($data, 'ifsc'),
            self::str($data, 'micr'),
            self::str($data, 'nbin'),
            self::str($data, 'address'),
            self::str($data, 'city'),
            self::str($data, 'state'),
            self::str($data, 'branch'),
            self::str($data, 'ifscSubcode'),
            self::str($data, 'category'),
            self::str($data, 'swiftCode'),
        ))->withRaw($data);
    }
}
