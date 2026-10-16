<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Http\RequestOptions;
use StampMitra\Model\PaymentIntent;

final class PaymentIntents extends AbstractResource
{
    /**
     * POST /v1/payments/intents (auto Idempotency-Key unless you pass one).
     *
     * @param array<string, mixed> $body
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function create(array $body, RequestOptions|array|null $options = null): PaymentIntent
    {
        return $this->call(PaymentIntent::class, 'POST', '/v1/payments/intents', $body, [], $options, true);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function get(string $id, RequestOptions|array|null $options = null): PaymentIntent
    {
        return $this->call(PaymentIntent::class, 'GET', '/v1/payments/intents/' . $this->segment($id, 'id'), null, [], $options);
    }
}
