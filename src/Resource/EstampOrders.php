<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Exception\ValidationError;
use StampMitra\Http\RequestOptions;
use StampMitra\Http\Transport;
use StampMitra\Model\EstampDocument;
use StampMitra\Model\EstampOrder;
use StampMitra\Model\EstampOrderExport;
use StampMitra\Model\EstampOrderList;
use StampMitra\Model\EstampStampLine;
use StampMitra\Model\EstampStampList;
use StampMitra\Model\EstampValidateResult;

/**
 * e-Stamp orders: `$client->estamp->orders`. One order is one state, at most
 * 5000 stamp lines. A line is an array or an {@see EstampStampLine}.
 */
final class EstampOrders extends AbstractResource
{
    private const MAX_LINES = 5000;
    private const MAX_QUANTITY = 1000;
    private const MAX_FILE_BYTES = 10 * 1024 * 1024;

    /**
     * POST /v1/estamp/orders/validate: a dry run (nothing is created, not billed). Safe to retry, so it is.
     *
     * @param array<string, mixed> $body {reference?, stamps: list<array|EstampStampLine>}
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function validate(array $body, RequestOptions|array|null $options = null): EstampValidateResult
    {
        return $this->call(EstampValidateResult::class, 'POST', '/v1/estamp/orders/validate', $this->orderBody($body), [], $options, false, Transport::RETRY_SAFE);
    }

    /**
     * POST /v1/estamp/orders. The API requires an Idempotency-Key: one is
     * generated when you do not pass `idempotencyKey` in the options, and it
     * is reused across automatic retries. This debits both wallets: a 402
     * says which one is short (`$e->getWallet()`).
     *
     * @param array<string, mixed> $body {reference?, stamps: list<array|EstampStampLine>}
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function create(array $body, RequestOptions|array|null $options = null): EstampOrder
    {
        return $this->call(EstampOrder::class, 'POST', '/v1/estamp/orders', $this->orderBody($body), [], $options, true);
    }

    /**
     * GET /v1/estamp/orders.
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function list(?int $page = null, ?int $limit = null, ?string $cursor = null, RequestOptions|array|null $options = null): EstampOrderList
    {
        if ($this->validateInput && $limit !== null && ($limit < 1 || $limit > 100)) {
            throw ValidationError::forField('limit', 'limit must be between 1 and 100');
        }

        return $this->call(EstampOrderList::class, 'GET', '/v1/estamp/orders', null, ['page' => $page, 'limit' => $limit, 'cursor' => $cursor], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function get(string $id, RequestOptions|array|null $options = null): EstampOrder
    {
        return $this->call(EstampOrder::class, 'GET', '/v1/estamp/orders/' . $this->segment($id, 'id'), null, [], $options);
    }

    /**
     * POST /v1/estamp/orders/{id}/cancel. Never retried automatically.
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function cancel(string $id, RequestOptions|array|null $options = null): EstampOrder
    {
        return $this->call(EstampOrder::class, 'POST', '/v1/estamp/orders/' . $this->segment($id, 'id') . '/cancel', null, [], $options, false, Transport::RETRY_NEVER);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function export(string $id, RequestOptions|array|null $options = null): EstampOrderExport
    {
        return $this->call(EstampOrderExport::class, 'GET', '/v1/estamp/orders/' . $this->segment($id, 'id') . '/export', null, [], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function listStamps(string $id, RequestOptions|array|null $options = null): EstampStampList
    {
        return $this->call(EstampStampList::class, 'GET', '/v1/estamp/orders/' . $this->segment($id, 'id') . '/stamps', null, [], $options);
    }

    /**
     * POST /v1/estamp/orders/{orderId}/documents (multipart/form-data): attach the
     * payer's PAN card. Advisory: an order is never held back without it.
     *
     * $file is a path to a PDF, JPEG or PNG (at most 10 MB), an open stream, or
     * ['contents' => string, 'filename' => string, 'contentType' => ?string]. The
     * file is read once and re-sent in full on a retry. An Idempotency-Key is
     * generated unless you pass one.
     *
     * @param string|resource|array{contents: string, filename: string, contentType?: string} $file
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function uploadDocument(string $orderId, mixed $file, string $type = 'pan_card', RequestOptions|array|null $options = null): EstampDocument
    {
        [$contents, $filename, $contentType] = $this->readFile($file);

        return $this->callMultipart(EstampDocument::class, 'POST', '/v1/estamp/orders/' . $this->segment($orderId, 'orderId') . '/documents', [
            ['name' => 'file', 'contents' => $contents, 'filename' => $filename, 'contentType' => $contentType],
            ['name' => 'type', 'contents' => $type],
        ], $options, true);
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function orderBody(array $body): array
    {
        $stamps = $body['stamps'] ?? null;
        if (is_array($stamps)) {
            $lines = [];
            foreach ($stamps as $line) {
                $lines[] = $line instanceof EstampStampLine ? $line->toArray() : $line;
            }
            $body['stamps'] = $lines;
        }
        if ($this->validateInput) {
            if (!is_array($stamps) || $stamps === []) {
                throw ValidationError::forField('stamps', 'stamps must be a non-empty list');
            }
            if (count($stamps) > self::MAX_LINES) {
                throw ValidationError::forField('stamps', 'an order holds at most ' . self::MAX_LINES . ' stamp lines');
            }
            $states = [];
            foreach ($body['stamps'] as $i => $line) {
                if (!is_array($line)) {
                    throw ValidationError::forField("stamps[$i]", 'a stamp line must be an array or an EstampStampLine');
                }
                if (isset($line['quantity']) && (!is_int($line['quantity']) || $line['quantity'] < 1 || $line['quantity'] > self::MAX_QUANTITY)) {
                    throw ValidationError::forField("stamps[$i].quantity", 'quantity must be an integer between 1 and ' . self::MAX_QUANTITY);
                }
                if (isset($line['state']) && is_string($line['state'])) {
                    $states[strtoupper($line['state'])] = true;
                }
            }
            if (count($states) > 1) {
                throw ValidationError::forField('stamps', 'one order is one state: create one order per state');
            }
        }

        return $body;
    }

    /** @return array{string, string, string} contents, filename, content type */
    private function readFile(mixed $file): array
    {
        $contents = null;
        $filename = 'pan-card';
        $contentType = null;
        if (is_array($file)) {
            $contents = $file['contents'] ?? null;
            $filename = is_string($file['filename'] ?? null) ? $file['filename'] : $filename;
            $contentType = is_string($file['contentType'] ?? null) ? $file['contentType'] : null;
        } elseif (is_string($file)) {
            if (!is_file($file) || !is_readable($file)) {
                throw ValidationError::forField('file', 'file is not a readable path');
            }
            if ($this->validateInput && filesize($file) > self::MAX_FILE_BYTES) {
                throw ValidationError::forField('file', 'file must be at most 10 MB');
            }
            $contents = file_get_contents($file);
            $filename = basename($file);
        } elseif (is_resource($file)) {
            $contents = stream_get_contents($file);
        }
        if (!is_string($contents) || $contents === '') {
            throw ValidationError::forField('file', 'file must be a readable path, a stream or an array with contents');
        }
        if ($this->validateInput && strlen($contents) > self::MAX_FILE_BYTES) {
            throw ValidationError::forField('file', 'file must be at most 10 MB');
        }
        $sniffed = match (true) {
            str_starts_with($contents, '%PDF') => 'application/pdf',
            str_starts_with($contents, "\xFF\xD8\xFF") => 'image/jpeg',
            str_starts_with($contents, "\x89PNG\r\n\x1a\n") => 'image/png',
            default => null,
        };
        if ($contentType === null) {
            $contentType = $sniffed;
        }
        if ($contentType === null) {
            if ($this->validateInput) {
                throw ValidationError::forField('file', 'file must be a PDF, JPEG or PNG');
            }
            $contentType = 'application/octet-stream';
        }

        return [$contents, $filename, $contentType];
    }
}
