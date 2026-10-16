<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Http\RequestOptions;
use StampMitra\Model\GenericResponse;

final class WebhookEvents extends AbstractResource
{
    /** @param RequestOptions|array<string, mixed>|null $options */
    public function list(?int $limit = null, ?string $cursor = null, RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'GET', '/v1/webhooks/events', null, ['limit' => $limit, 'cursor' => $cursor], $options);
    }

    /**
     * POST /v1/webhooks/events/{id}/replay. Not retried automatically unless you pass an idempotencyKey.
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function replay(string $id, RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'POST', '/v1/webhooks/events/' . $this->segment($id, 'id') . '/replay', null, [], $options);
    }
}
