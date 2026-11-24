<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use StampMitra\StampMitra;

$client = new StampMitra();

echo 'Stamp Duty wallet: ', (string) $client->stampDutyWallet->balance()->balance, "\n";
echo 'Service Fee wallet: ', (string) $client->serviceFeeWallet->balance()->balance, "\n";

// Top-ups are never retried automatically. Show your payer the figures from the response; the SDK does not compute fees or GST.
$topup = $client->stampDutyWallet->topup(1000);
echo 'Pay ', (string) $topup->totalCharged, ' (fee ', (string) $topup->processingFeeAmount, ' at ', (string) $topup->processingFeePercent, '%, GST ', (string) $topup->gstAmount, ")\n";
echo 'The wallet is credited ', (string) $topup->creditAmount, " when the payment succeeds.\n";

// The GST tax invoice for the fee is listed once the top-up is paid.
foreach ($client->stampDutyWallet->listInvoices()->items as $invoice) {
    echo (string) $invoice->invoiceNumber, ' ', (string) $invoice->totalCharged, "\n";
}
