<?php

declare(strict_types=1);

namespace StampMitra\Exception;

/**
 * One finding of a rejected e-Stamp order body (error.details.errors[] item).
 */
final class FieldError
{
    public function __construct(
        public readonly ?string $field = null,
        public readonly ?string $code = null,
        public readonly ?string $message = null,
        /** Zero-based index of the stamp line, when the API gives one. */
        public readonly ?int $index = null,
    ) {
    }

    /** @param array<array-key, mixed> $item */
    public static function fromArray(array $item): self
    {
        return new self(
            isset($item['field']) && is_string($item['field']) ? $item['field'] : null,
            isset($item['code']) && is_string($item['code']) ? $item['code'] : null,
            isset($item['message']) && is_string($item['message']) ? $item['message'] : null,
            isset($item['index']) && is_int($item['index']) ? $item['index'] : null,
        );
    }
}
