<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Exception\ValidationError;
use StampMitra\Http\ApiResponse;
use StampMitra\Http\RequestOptions;
use StampMitra\Http\Transport;
use StampMitra\Model\Model;

abstract class AbstractResource
{
    public function __construct(
        protected readonly Transport $transport,
        protected readonly bool $validateInput = true,
    ) {
    }

    /**
     * @template T of Model
     * @param class-string<T> $class
     * @param array<string, mixed>|null $body
     * @param array<string, scalar|null> $query
     * @param RequestOptions|array<string, mixed>|null $options
     * @return T
     */
    protected function call(
        string $class,
        string $method,
        string $path,
        ?array $body = null,
        array $query = [],
        RequestOptions|array|null $options = null,
        bool $autoIdempotency = false,
    ): Model {
        $response = $this->transport->request($method, $path, $body, $query, RequestOptions::from($options), $autoIdempotency);

        return $this->hydrate($class, $response);
    }

    /**
     * @template T of Model
     * @param class-string<T> $class
     * @return T
     */
    protected function hydrate(string $class, ApiResponse $response): Model
    {
        return $class::fromArray($response->data)->withResponse($response);
    }

    /** Percent-encode a path segment, rejecting empty ids locally. */
    protected function segment(string $value, string $name): string
    {
        if (trim($value) === '') {
            throw ValidationError::forField($name, "$name must not be empty");
        }

        return rawurlencode($value);
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    protected function compact(array $body): array
    {
        return array_filter($body, static fn ($v): bool => $v !== null);
    }
}
