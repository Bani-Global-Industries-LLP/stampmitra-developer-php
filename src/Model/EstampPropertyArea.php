<?php

declare(strict_types=1);

namespace StampMitra\Model;

/** Property area of an e-Stamp line (required by some states). */
final class EstampPropertyArea
{
    public const SQUARE_FEET = 'Square Feet';
    public const SQUARE_YARD = 'Square Yard';
    public const SQUARE_METERS = 'Square Meters';
    public const NOT_APPLICABLE = 'Not Applicable';

    public function __construct(
        public readonly string $unit,
        public readonly int|float|null $value = null,
    ) {
    }

    /** @return array<string, int|float|string> */
    public function toArray(): array
    {
        return array_filter(['unit' => $this->unit, 'value' => $this->value], static fn ($v): bool => $v !== null);
    }
}
