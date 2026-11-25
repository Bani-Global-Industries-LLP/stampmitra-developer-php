<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use StampMitra\Exception\PaymentRequiredError;
use StampMitra\Exception\ValidationError;
use StampMitra\Model\EstampParty;
use StampMitra\Model\EstampStampLine;
use StampMitra\StampMitra;

// validate -> create (with an idempotency key) -> upload the PAN card -> list stamps -> certificate.
$client = new StampMitra();

$body = [
    'reference' => 'order-2026-0917',
    'stamps' => [
        new EstampStampLine(
            state: 'MH',
            articleCode: 'ART1',
            denomination: 500,
            firstParty: new EstampParty('Jane Doe', address: '12 MG Road, Pune'),
            secondParty: 'Acme Pvt Ltd',
            clientReference: 'line-001',
            purpose: 'Rental Agreement',
        ),
    ],
];

// A dry run: nothing is created and nothing is billed.
$check = $client->estamp->orders->validate($body);
if (!$check->valid) {
    echo json_encode($check->errors), "\n";
    exit(1);
}

try {
    // Creating an order debits both wallets. The same idempotency key replays the same order, so a retry never creates a second one.
    $order = $client->estamp->orders->create($body, ['idempotencyKey' => 'order-2026-0917']);
} catch (PaymentRequiredError $e) {
    echo 'Top up the ', $e->getWallet() ?? 'Stamp duty or Service', " wallet first.\n";
    exit(1);
} catch (ValidationError $e) {
    foreach ($e->getFieldErrors() as $issue) {
        echo "$issue->field: $issue->code $issue->message\n";
    }
    exit(1);
}
echo 'order ', (string) $order->id, ' ', (string) $order->status, "\n";

// The PAN card: an order can be placed without it, but a PAN card is mandatory to complete the stamp.
$client->estamp->orders->uploadDocument((string) $order->id, __DIR__ . '/pan.pdf');

foreach ($client->estamp->orders->listStamps((string) $order->id)->items as $stamp) {
    $certificate = $client->estamp->stamps->getCertificate((string) $stamp->id);
    echo (string) $stamp->id, ': ', $certificate->available ? 'certificate ready, download ' . (string) $certificate->downloadUrl . ' now (it expires)' : 'certificate not yet available', "\n";
}
