<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Response whose schema the API does not fix yet (e-Stamp schemas, orders,
 * payments, webhook events ...). Read fields with get() or raw().
 */
final class GenericResponse extends Model
{
    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self())->withRaw($data);
    }

    /**
     * Items of a list-shaped response ({"items": [...]} or a bare JSON array).
     *
     * @return list<mixed>
     */
    public function items(): array
    {
        $items = $this->raw['items'] ?? [];

        return is_array($items) ? array_values($items) : [];
    }

    public function nextCursor(): ?string
    {
        $c = $this->raw['nextCursor'] ?? null;

        return is_string($c) && $c !== '' ? $c : null;
    }
}
