<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Result of GET /v1/orders/{id}/documents/{documentId}/download.
 */
final class DocumentDownloadUrl extends Model
{
    public function __construct(
        public readonly ?string $downloadUrl = null,
        public readonly mixed $expiresAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::str($data, 'downloadUrl'),
            $data['expiresAt'] ?? null,
        ))->withRaw($data);
    }
}
