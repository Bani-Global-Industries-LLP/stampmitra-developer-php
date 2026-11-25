<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Exception\ValidationError;
use StampMitra\Http\RequestOptions;
use StampMitra\Http\Transport;
use StampMitra\Model\EstampCertificate;
use StampMitra\Model\EstampPanCardResult;
use StampMitra\Model\EstampStamp;

/** Individual e-Stamps: `$client->estamp->stamps`. */
final class EstampStamps extends AbstractResource
{
    /** @param RequestOptions|array<string, mixed>|null $options */
    public function get(string $stampId, RequestOptions|array|null $options = null): EstampStamp
    {
        return $this->call(EstampStamp::class, 'GET', '/v1/estamp/stamps/' . $this->segment($stampId, 'stampId'), null, [], $options);
    }

    /**
     * POST /v1/estamp/stamps/{stampId}/cancel. Never retried automatically.
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function cancel(string $stampId, RequestOptions|array|null $options = null): EstampStamp
    {
        return $this->call(EstampStamp::class, 'POST', '/v1/estamp/stamps/' . $this->segment($stampId, 'stampId') . '/cancel', null, [], $options, false, Transport::RETRY_NEVER);
    }

    /**
     * POST /v1/estamp/stamps/{stampId}/replace. Never retried automatically.
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function replace(string $stampId, RequestOptions|array|null $options = null): EstampStamp
    {
        return $this->call(EstampStamp::class, 'POST', '/v1/estamp/stamps/' . $this->segment($stampId, 'stampId') . '/replace', null, [], $options, false, Transport::RETRY_NEVER);
    }

    /**
     * POST /v1/estamp/stamps/{stampId}/pan-card: link the payer's PAN card by https URL. Answers 202
     * (processed asynchronously: poll get() and read panCard). A PAN card is mandatory to complete the stamp. Never retried automatically.
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function setPanCard(string $stampId, string $panCardImage, RequestOptions|array|null $options = null): EstampPanCardResult
    {
        if ($this->validateInput && !str_starts_with(strtolower($panCardImage), 'https://')) {
            throw ValidationError::forField('panCardImage', 'panCardImage must be an https link');
        }

        return $this->call(EstampPanCardResult::class, 'POST', '/v1/estamp/stamps/' . $this->segment($stampId, 'stampId') . '/pan-card', ['panCardImage' => $panCardImage], [], $options, false, Transport::RETRY_NEVER);
    }

    /**
     * GET /v1/estamp/stamps/{stampId}/certificate. Always 200: check `available`. downloadUrl is
     * short-lived (5 minutes): download and store the file promptly.
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function getCertificate(string $stampId, RequestOptions|array|null $options = null): EstampCertificate
    {
        return $this->call(EstampCertificate::class, 'GET', '/v1/estamp/stamps/' . $this->segment($stampId, 'stampId') . '/certificate', null, [], $options);
    }
}
