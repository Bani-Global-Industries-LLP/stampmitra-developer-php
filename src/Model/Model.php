<?php

declare(strict_types=1);

namespace StampMitra\Model;

use StampMitra\Http\ApiResponse;

/**
 * Base class of every typed response. Typed properties are read-only; every
 * field the server sent, including ones this SDK version does not know about,
 * stays available through {@see raw()}.
 */
abstract class Model implements \JsonSerializable
{
    /** @var array<string, mixed> */
    protected array $raw = [];

    private ?ApiResponse $response = null;

    /**
     * @param array<string, mixed> $data
     */
    abstract public static function fromArray(array $data): static;

    /**
     * The complete decoded response body, unknown fields included.
     *
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->raw;
    }

    /** Read any field of the raw body, including ones this SDK version does not model. */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->raw[$key] ?? $default;
    }

    /** HTTP metadata (status, headers, request id) when the model came from a call. */
    public function response(): ?ApiResponse
    {
        return $this->response;
    }

    public function withResponse(ApiResponse $response): static
    {
        $clone = clone $this;
        $clone->response = $response;

        return $clone;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }

    /**
     * @param array<string, mixed> $raw
     */
    protected function withRaw(array $raw): static
    {
        $this->raw = $raw;

        return $this;
    }

    /** @param array<string, mixed> $d */
    protected static function str(array $d, string ...$keys): ?string
    {
        foreach ($keys as $k) {
            if (isset($d[$k]) && (is_string($d[$k]) || is_int($d[$k]) || is_float($d[$k]))) {
                return (string) $d[$k];
            }
        }

        return null;
    }

    /** @param array<string, mixed> $d */
    protected static function bool(array $d, string $key): ?bool
    {
        return isset($d[$key]) && is_bool($d[$key]) ? $d[$key] : null;
    }

    /** @param array<string, mixed> $d */
    protected static function int(array $d, string $key): ?int
    {
        return isset($d[$key]) && is_numeric($d[$key]) ? (int) $d[$key] : null;
    }

    /**
     * @param array<string, mixed> $d
     * @return array<array-key, mixed>|null
     */
    protected static function arr(array $d, string $key): ?array
    {
        return isset($d[$key]) && is_array($d[$key]) ? $d[$key] : null;
    }

    /**
     * @template T of Model
     * @param array<string, mixed> $d
     * @param class-string<T> $class
     * @return T|null
     */
    protected static function obj(array $d, string $key, string $class): ?Model
    {
        return isset($d[$key]) && is_array($d[$key]) && !array_is_list($d[$key]) ? $class::fromArray($d[$key]) : null;
    }

    /**
     * @template T of Model
     * @param array<string, mixed> $d
     * @param class-string<T> $class
     * @return list<T>
     */
    protected static function list(array $d, string $key, string $class, ?string $fallbackKey = null): array
    {
        $out = [];
        if (!isset($d[$key]) && $fallbackKey !== null) {
            $key = $fallbackKey;
        }
        if (isset($d[$key]) && is_array($d[$key])) {
            foreach ($d[$key] as $item) {
                if (is_array($item)) {
                    /** @var array<string, mixed> $item */
                    $out[] = $class::fromArray($item);
                }
            }
        }

        return $out;
    }
}
