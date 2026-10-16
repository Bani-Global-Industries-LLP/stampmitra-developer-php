<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use StampMitra\Exception\SignatureVerificationError;
use StampMitra\Webhook;

$secret = getenv('STAMPMITRA_WEBHOOK_SECRET') ?: '';

// The signature covers the raw bytes: read the body untouched, never re-encode it.
$payload = (string) file_get_contents('php://input');

try {
    $event = Webhook::constructEvent(
        $payload,
        $_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'] ?? '',
        $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '',
        $secret,
        eventId: $_SERVER['HTTP_X_WEBHOOK_EVENT_ID'] ?? null,
    );
} catch (SignatureVerificationError $e) {
    http_response_code(400);
    echo 'invalid signature';
    exit;
}

// Handle idempotently: deliveries are retried, so de-duplicate on $event->eventId.
error_log(sprintf('order %s -> %s', (string) $event->orderId, (string) $event->coreEventType));

http_response_code(200);
echo 'ok';
