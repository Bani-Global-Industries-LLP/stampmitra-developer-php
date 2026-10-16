<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Exception\ValidationError;
use StampMitra\Http\RequestOptions;
use StampMitra\Model\DocumentDownloadUrl;
use StampMitra\Model\OrderDetail;
use StampMitra\Model\OrderDocumentsResponse;
use StampMitra\Model\OrderPaymentResponse;
use StampMitra\Model\OrderReference;
use StampMitra\Model\OrderStatusResponse;

final class Orders extends AbstractResource
{
    /**
     * POST /v1/orders. Requires externalOrderId, externalCustomerId and
     * serviceCode; an Idempotency-Key is generated automatically (and reused
     * across retries) unless you pass one.
     *
     * @param array<string, mixed> $body
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function create(array $body, RequestOptions|array|null $options = null): OrderReference
    {
        if ($this->validateInput) {
            foreach (['externalOrderId', 'externalCustomerId', 'serviceCode'] as $required) {
                if (!isset($body[$required]) || !is_string($body[$required]) || trim($body[$required]) === '') {
                    throw ValidationError::forField($required, "$required is required");
                }
            }
        }

        return $this->call(OrderReference::class, 'POST', '/v1/orders', $body, [], $options, true);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function get(string $id, RequestOptions|array|null $options = null): OrderDetail
    {
        return $this->call(OrderDetail::class, 'GET', '/v1/orders/' . $this->segment($id, 'id'), null, [], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function getStatus(string $id, RequestOptions|array|null $options = null): OrderStatusResponse
    {
        return $this->call(OrderStatusResponse::class, 'GET', '/v1/orders/' . $this->segment($id, 'id') . '/status', null, [], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function getPayment(string $id, RequestOptions|array|null $options = null): OrderPaymentResponse
    {
        return $this->call(OrderPaymentResponse::class, 'GET', '/v1/orders/' . $this->segment($id, 'id') . '/payment', null, [], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function listDocuments(string $id, RequestOptions|array|null $options = null): OrderDocumentsResponse
    {
        return $this->call(OrderDocumentsResponse::class, 'GET', '/v1/orders/' . $this->segment($id, 'id') . '/documents', null, [], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function getDocumentDownload(string $id, string $documentId, RequestOptions|array|null $options = null): DocumentDownloadUrl
    {
        return $this->call(DocumentDownloadUrl::class, 'GET', '/v1/orders/' . $this->segment($id, 'id') . '/documents/' . $this->segment($documentId, 'documentId') . '/download', null, [], $options);
    }
}
