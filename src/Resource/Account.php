<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Http\RequestOptions;
use StampMitra\Model\GenericResponse;

/** Account balance and usage (`$client->account`). */
final class Account extends AbstractResource
{
    /**
     * GET /v1/account/balance.
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function balance(RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'GET', '/v1/account/balance', null, [], $options);
    }

    /**
     * GET /v1/account/usage.
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function usage(RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'GET', '/v1/account/usage', null, [], $options);
    }
}
