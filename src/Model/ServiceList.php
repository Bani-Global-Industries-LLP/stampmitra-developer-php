<?php

declare(strict_types=1);

namespace StampMitra\Model;

/**
 * Result of GET /v1/services (the API returns a bare array; also accepts {services: [...]}).
 */
final class ServiceList extends Model
{
    public function __construct(
        /** @var list<Service> */
        public readonly array $services = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return (new self(
            self::list($data, 'services', Service::class, 'items'),
        ))->withRaw($data);
    }
}
