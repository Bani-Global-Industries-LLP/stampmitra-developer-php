<?php

declare(strict_types=1);

namespace StampMitra\Exception;

/**
 * HTTP 400 from the API, or a client-side validation failure raised before
 * anything is sent (status null; nothing is billed).
 */
class ValidationError extends StampMitraError
{
    /** Build a local (pre-flight) validation error. */
    public static function forField(string $field, string $message): self
    {
        return new self($message, null, 'VALIDATION_ERROR', null, null, $field);
    }
}
