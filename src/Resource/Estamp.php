<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Exception\ValidationError;
use StampMitra\Http\RequestOptions;
use StampMitra\Http\Transport;
use StampMitra\Model\EstampRequirements;
use StampMitra\Model\EstampWallet;
use StampMitra\Model\GenericResponse;

/**
 * e-Stamp Paper reference data and helpers. The e-Stamp field shapes are not
 * finalised by the API, so bodies are plain arrays and responses are
 * {@see GenericResponse} (use ->raw() / ->get()).
 */
final class Estamp extends AbstractResource
{
    /** Orders: validate, create, list, get, cancel, export, listStamps, uploadDocument. */
    public readonly EstampOrders $orders;
    /** Stamps: get, cancel, replace, setPanCard, getCertificate. */
    public readonly EstampStamps $stamps;

    public function __construct(Transport $transport, bool $validateInput = true)
    {
        parent::__construct($transport, $validateInput);
        $this->orders = new EstampOrders($transport, $validateInput);
        $this->stamps = new EstampStamps($transport, $validateInput);
    }

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

    /**
     * GET /v1/estamp/states/availability (optional `active` filter).
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function getStatesAvailability(?bool $active = null, RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'GET', '/v1/estamp/states/availability', null, ['active' => $active], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function getState(string $stateCode, RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'GET', '/v1/estamp/states/' . $this->segment($stateCode, 'stateCode'), null, [], $options);
    }

    /**
     * GET /v1/estamp/states/{stateCode}/requirements. The result's `exampleBody` is a ready-to-edit order body.
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function getStateRequirements(string $stateCode, ?string $articleCode = null, int|float|null $denomination = null, ?string $category = null, RequestOptions|array|null $options = null): EstampRequirements
    {
        if ($this->validateInput && $category !== null && !in_array($category, ['e_stamp', 'traditional_stamp'], true)) {
            throw ValidationError::forField('category', "category must be 'e_stamp' or 'traditional_stamp'");
        }

        return $this->call(EstampRequirements::class, 'GET', '/v1/estamp/states/' . $this->segment($stateCode, 'stateCode') . '/requirements', null, ['articleCode' => $articleCode, 'denomination' => $denomination, 'category' => $category], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function listDistricts(string $stateCode, RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'GET', '/v1/estamp/states/' . $this->segment($stateCode, 'stateCode') . '/districts', null, [], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function listSros(string $stateCode, string $districtCode, RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'GET', '/v1/estamp/states/' . $this->segment($stateCode, 'stateCode') . '/districts/' . $this->segment($districtCode, 'districtCode') . '/sros', null, [], $options);
    }

    /**
     * GET /v1/estamp/wallet: both wallets, the Stamp duty wallet and the Service wallet (needs both wallet read scopes).
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function getWallet(RequestOptions|array|null $options = null): EstampWallet
    {
        return $this->call(EstampWallet::class, 'GET', '/v1/estamp/wallet', null, [], $options);
    }
}
