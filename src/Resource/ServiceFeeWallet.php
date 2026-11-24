<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Exception\ValidationError;
use StampMitra\Http\RequestOptions;
use StampMitra\Http\Transport;
use StampMitra\Model\GenericResponse;
use StampMitra\Model\ServiceFeeWalletTopup;
use StampMitra\Model\WalletBalance;
use StampMitra\Model\WalletInvoice;
use StampMitra\Model\WalletInvoicePage;

/**
 * Service Fee wallet (`$client->serviceFeeWallet`). topup() adds GST on baseAmount and the wallet is credited baseAmount.
 * Top-ups are never retried automatically and carry no Idempotency-Key. Show
 * your payer the amounts from the response: the SDK never computes fees or GST.
 */
final class ServiceFeeWallet extends AbstractResource
{
    /** @param RequestOptions|array<string, mixed>|null $options */
    public function balance(RequestOptions|array|null $options = null): WalletBalance
    {
        return $this->call(WalletBalance::class, 'GET', '/v1/service-fee-wallet', null, [], $options);
    }

    /**
     * POST /v1/service-fee-wallet/topup {baseAmount} (rupees, positive). Never retried automatically.
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function topup(int|float $baseAmount, RequestOptions|array|null $options = null): ServiceFeeWalletTopup
    {
        if ($this->validateInput && $baseAmount <= 0) {
            throw ValidationError::forField('baseAmount', 'baseAmount must be a positive number');
        }

        return $this->call(ServiceFeeWalletTopup::class, 'POST', '/v1/service-fee-wallet/topup', ['baseAmount' => $baseAmount], [], $options, false, Transport::RETRY_NEVER);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function listTransactions(?int $limit = null, ?string $cursor = null, RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'GET', '/v1/service-fee-wallet/transactions', null, ['limit' => $limit, 'cursor' => $cursor], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function listInvoices(?int $limit = null, ?string $cursor = null, RequestOptions|array|null $options = null): WalletInvoicePage
    {
        return $this->call(WalletInvoicePage::class, 'GET', '/v1/service-fee-wallet/invoices', null, ['limit' => $limit, 'cursor' => $cursor], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function getInvoice(string $id, RequestOptions|array|null $options = null): WalletInvoice
    {
        return $this->call(WalletInvoice::class, 'GET', '/v1/service-fee-wallet/invoices/' . $this->segment($id, 'id'), null, [], $options);
    }
}
