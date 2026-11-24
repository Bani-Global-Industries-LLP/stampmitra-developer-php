<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * One stamp line of an e-Stamp order (at most 5000 lines per order, one state
 * per order, quantity at most 1000). Plain arrays with the same keys work too.
 */
final class EstampStampLine
{
    public function __construct(
        public readonly string $state,
        public readonly string $articleCode,
        public readonly int|float $denomination,
        public readonly string|EstampParty $firstParty,
        public readonly string|EstampParty|null $secondParty = null,
        public readonly ?string $clientReference = null,
        /** 'e_stamp' or 'traditional_stamp' */
        public readonly ?string $category = null,
        public readonly ?string $stampDutyPayer = null,
        public readonly ?string $payerPhone = null,
        public readonly ?string $payerGender = null,
        public readonly ?string $purpose = null,
        public readonly ?string $district = null,
        public readonly ?string $sro = null,
        public readonly int|float|null $cessAmount = null,
        public readonly ?EstampPropertyArea $propertyArea = null,
        /** https link to the payer's PAN card (advisory) */
        public readonly ?string $panCardImage = null,
        public readonly ?string $costCentre = null,
        public readonly ?string $businessUnit = null,
        public readonly ?string $linkedDocRef = null,
        public readonly ?int $quantity = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $party = static fn (string|EstampParty|null $p): string|array|null => $p instanceof EstampParty ? $p->toArray() : $p;

        return array_filter([
            'clientReference' => $this->clientReference,
            'state' => $this->state,
            'category' => $this->category,
            'articleCode' => $this->articleCode,
            'denomination' => $this->denomination,
            'stampDutyPayer' => $this->stampDutyPayer,
            'payerPhone' => $this->payerPhone,
            'payerGender' => $this->payerGender,
            'firstParty' => $party($this->firstParty),
            'secondParty' => $party($this->secondParty),
            'purpose' => $this->purpose,
            'district' => $this->district,
            'sro' => $this->sro,
            'cessAmount' => $this->cessAmount,
            'propertyArea' => $this->propertyArea?->toArray(),
            'panCardImage' => $this->panCardImage,
            'costCentre' => $this->costCentre,
            'businessUnit' => $this->businessUnit,
            'linkedDocRef' => $this->linkedDocRef,
            'quantity' => $this->quantity,
        ], static fn ($v): bool => $v !== null);
    }
}
