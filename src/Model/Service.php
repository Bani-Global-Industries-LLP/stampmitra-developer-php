<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * An e-Stamp service (GET /v1/services).
 */
final class Service extends Model
{
    public function __construct(
        public readonly ?string $serviceCode = null,
        public readonly ?string $name = null,
        public readonly ?string $category = null,
        public readonly ?string $basePrice = null,
        public readonly ?string $currency = null,
        public readonly ?bool $isActive = null,
        public readonly ?string $gstRatePercent = null,
        public readonly ?string $defaultGovernmentFee = null,
        public readonly ?int $processingTimeHours = null,
        public readonly ?bool $guestCheckoutEnabled = null,
        public readonly ?bool $requiresDocuments = null,
        /** @var array<array-key, mixed>|null */
        public readonly ?array $uiSchema = null,
        public readonly ?bool $entitled = null,
        public readonly ?string $entitlementUpdatedAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'serviceCode'),
            self::str($data, 'name'),
            self::str($data, 'category'),
            self::str($data, 'basePrice'),
            self::str($data, 'currency'),
            self::bool($data, 'isActive'),
            self::str($data, 'gstRatePercent'),
            self::str($data, 'defaultGovernmentFee'),
            self::int($data, 'processingTimeHours'),
            self::bool($data, 'guestCheckoutEnabled'),
            self::bool($data, 'requiresDocuments'),
            self::arr($data, 'uiSchema'),
            self::bool($data, 'entitled'),
            self::str($data, 'entitlementUpdatedAt'),
        ))->withRaw($data);
    }
}
