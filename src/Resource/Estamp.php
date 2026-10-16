<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Http\RequestOptions;
use StampMitra\Model\GenericResponse;

/**
 * e-Stamp Paper reference data and helpers. The e-Stamp field shapes are not
 * finalised by the API, so bodies are plain arrays and responses are
 * {@see GenericResponse} (use ->raw() / ->get()).
 */
final class Estamp extends AbstractResource
{
    /** @param RequestOptions|array<string, mixed>|null $options */
    public function getSchema(string $serviceCode, RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'GET', '/v1/estamp/schema/' . $this->segment($serviceCode, 'serviceCode'), null, [], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function listStates(RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'GET', '/v1/estamp/states', null, [], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function listInstruments(string $stateCode, RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'GET', '/v1/estamp/states/' . $this->segment($stateCode, 'stateCode') . '/instruments', null, [], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function listArticles(string $instrumentCode, RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'GET', '/v1/estamp/instruments/' . $this->segment($instrumentCode, 'instrumentCode') . '/articles', null, [], $options);
    }

    /**
     * @param array<string, mixed> $body
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function calculateDuty(array $body, RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'POST', '/v1/estamp/duty/calculate', $body, [], $options, true);
    }

    /**
     * POST /v1/estamp/orders/{orderId}/parties. An Idempotency-Key is generated
     * automatically (and reused across retries) unless you pass one.
     *
     * @param array<string, mixed> $body
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function addParties(string $orderId, array $body, RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'POST', '/v1/estamp/orders/' . $this->segment($orderId, 'orderId') . '/parties', $body, [], $options, true);
    }
}
