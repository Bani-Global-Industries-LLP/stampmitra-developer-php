<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * A party of an e-Stamp line. firstParty / secondParty also accept a plain
 * name string. gender is MALE, FEMALE or OTHER.
 */
final class EstampParty
{
    public const MALE = 'MALE';
    public const FEMALE = 'FEMALE';
    public const OTHER = 'OTHER';

    public function __construct(
        public readonly string $name,
        public readonly ?string $address = null,
        public readonly ?string $phone = null,
        public readonly ?string $fatherHusbandName = null,
        public readonly ?string $gender = null,
    ) {
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'fatherHusbandName' => $this->fatherHusbandName,
            'gender' => $this->gender,
        ], static fn ($v): bool => $v !== null);
    }
}
