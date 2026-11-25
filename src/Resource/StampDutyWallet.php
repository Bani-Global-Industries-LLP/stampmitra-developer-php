<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Exception\ValidationError;
use StampMitra\Http\RequestOptions;
use StampMitra\Http\Transport;
use StampMitra\Model\GenericResponse;
use StampMitra\Model\StampDutyWalletTopup;
use StampMitra\Model\WalletBalance;
use StampMitra\Model\WalletInvoice;
use StampMitra\Model\WalletInvoicePage;

/**
 * Stamp duty wallet (`$client->stampDutyWallet`): holds the statutory duty that orders debit. topup() adds a 6% processing fee plus 18% GST on that fee to baseAmount; the wallet is credited baseAmount in full and a GST tax invoice for the fee is available from listInvoices()/getInvoice().
 * Top-ups are never retried automatically and carry no Idempotency-Key. Show
 * your payer the amounts from the response: the SDK never computes fees or GST.
 */
final class StampDutyWallet extends AbstractResource
{
    /** @param RequestOptions|array<string, mixed>|null $options */
    public function balance(RequestOptions|array|null $options = null): WalletBalance
    {
        return $this->call(WalletBalance::class, 'GET', '/v1/stamp-duty-wallet', null, [], $options);
    }

    /**
     * POST /v1/stamp-duty-wallet/topup {baseAmount} (rupees, positive). Never retried automatically.
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function topup(int|float $baseAmount, RequestOptions|array|null $options = null): StampDutyWalletTopup
    {
        if ($this->validateInput && $baseAmount <= 0) {
            throw ValidationError::forField('baseAmount', 'baseAmount must be a positive number');
        }

        return $this->call(StampDutyWalletTopup::class, 'POST', '/v1/stamp-duty-wallet/topup', ['baseAmount' => $baseAmount], [], $options, false, Transport::RETRY_NEVER);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function listTransactions(?int $limit = null, ?string $cursor = null, RequestOptions|array|null $options = null): GenericResponse
    {
        return $this->call(GenericResponse::class, 'GET', '/v1/stamp-duty-wallet/transactions', null, ['limit' => $limit, 'cursor' => $cursor], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function listInvoices(?int $limit = null, ?string $cursor = null, RequestOptions|array|null $options = null): WalletInvoicePage
    {
        return $this->call(WalletInvoicePage::class, 'GET', '/v1/stamp-duty-wallet/invoices', null, ['limit' => $limit, 'cursor' => $cursor], $options);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function getInvoice(string $id, RequestOptions|array|null $options = null): WalletInvoice
    {
        return $this->call(WalletInvoice::class, 'GET', '/v1/stamp-duty-wallet/invoices/' . $this->segment($id, 'id'), null, [], $options);
    }
}
