<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use StampMitra\StampMitra;

$client = new StampMitra();

// An Idempotency-Key is generated for you and reused across automatic retries.
// Pass your own to make a retry from your side safe too:
//   $client->orders->create($body, ['idempotencyKey' => 'order-123']);
$order = $client->orders->create([
    'externalOrderId' => 'ord_ext_123',
    'externalCustomerId' => 'cust_ext_456',
    'serviceCode' => 'estamp.instrument.rent_agreement',
    'details' => ['state' => 'MH', 'stampValue' => 500],
]);

echo json_encode($order->raw(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
echo 'status: ', (string) $order->response()?->status, "\n";

// Orders are addressed by the externalOrderId you supplied.
$status = $client->orders->getStatus((string) $order->externalOrderId);
echo json_encode($status->raw()), "\n";
