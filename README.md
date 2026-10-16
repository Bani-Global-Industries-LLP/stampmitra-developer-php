# StampMitra PHP SDK

[![Packagist](https://img.shields.io/badge/packagist-stampmitra%2Fstampmitra--php-lightgrey)](https://packagist.org/packages/stampmitra/stampmitra-php)
[![CI](https://img.shields.io/badge/CI-placeholder-lightgrey)](.github/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

Official PHP client for the [StampMitra Developer API](https://developer.stampmitra.in/docs):
Indian identity verification (PAN, GSTIN, vehicle RC, Voter ID, driving licence, passport, CIN, Udyam, bank account)
and e-Stamp Paper. OpenAPI spec: <https://developer.stampmitra.in/openapi.json>.

> **Pre-1.0 (0.1.0):** the API surface may change in minor releases. Pin your version.

## Install

```bash
composer require stampmitra/stampmitra-php guzzlehttp/guzzle
```

Requires PHP 8.1+. The SDK talks PSR-18/PSR-17; any compatible client works
(Guzzle is auto-discovered, or pass your own `httpClient`).

## Quickstart

```php
use StampMitra\StampMitra;

$client = new StampMitra(apiKey: getenv('STAMPMITRA_API_KEY') ?: null); // or just new StampMitra()
$pan = $client->verify->pan('ABCDE1234F', 'Rahul Kumar');
echo $pan->valid ? 'valid' : 'invalid', ' ', $pan->nameMatchResult, ' ', $pan->referenceId, PHP_EOL;
```

## Authentication

Keys look like `sk_test_...` (free sandbox) or `sk_live_...` (metered). They are read from the
`STAMPMITRA_API_KEY` environment variable unless you pass `apiKey:`. Keys that do not match
`^sk_(test|live)_[A-Za-z0-9_-]{8,}$` are rejected unless `allowCustomKey: true`.
`$client->environment` is `'test'` or `'live'`.

> **Security:** server-side only. Never ship `sk_` keys in mobile apps, browser code or public repositories.

Constructor options: `apiKey`, `baseUrl`, `timeout` (30s), `maxRetries` (2), `httpClient`, `defaultHeaders`,
`appInfo: ['name'=>..,'version'=>..,'url'=>..]`, `logger` (PSR-3), `validateInput` (true), `allowCustomKey`.
Every method also accepts per-request options (a `RequestOptions` or array): `timeout`, `maxRetries`, `idempotencyKey`, `headers`.

## Resources

```php
// Verification (every call is billed, including valid=false results)
$client->verify->pan('ABCDE1234F', 'Rahul Kumar');
$client->verify->gstin('27ABCDE1234F1Z5');
$client->verify->rc('MH12AB1234');
$client->verify->voterId('ABC1234567');
$client->verify->drivingLicense('MH1420110012345', '1990-01-31');   // IP-allowlisted key required
$client->verify->passport('PA1079341954215', '1990-01-31');         // IP-allowlisted key required
$client->verify->cin('U74999DL2015PTC284009');
$client->verify->udyam('UDYAM-MH-12-1234567');
$client->verify->panToGstin('ABCDE1234F');
$client->verify->panToUdyam('ABCDE1234F');
$client->verify->bankAccount('000123456789', 'ICIC0001234', name: 'Rahul Kumar');

// Services and e-Stamp reference data
$client->services->list();                    // ->services: list<Service>
$client->services->get('estamp.instrument.rent_agreement');
$client->estamp->getSchema('estamp.instrument.rent_agreement');
$client->estamp->listStates();
$client->estamp->listInstruments('MH');
$client->estamp->listArticles('rent_agreement');
$client->estamp->calculateDuty(['stateCode' => 'MH', 'articleCode' => '5']);
$client->estamp->addParties('ord_123', ['customerName' => '...', 'partyName' => '...']);

// Orders and payments
$order = $client->orders->create(['externalOrderId' => 'o1', 'externalCustomerId' => 'c1', 'serviceCode' => 'estamp....']);
$client->orders->get('o1');
$client->orders->getStatus('o1');
$client->orders->getPayment('o1');
$client->orders->listDocuments('o1');
$client->orders->getDocumentDownload('o1', 'doc_1');
$client->payments->intents->create(['externalOrderId' => 'o1', 'amountMinor' => 50000, 'currency' => 'INR', 'paymentConnectionId' => '...']);
$client->payments->intents->get('pi_1');

// Usage, logs, webhook events
$client->usage->summary();
$client->logs->list(['service' => 'pan.verify', 'status' => 'success', 'limit' => 50]);
$client->webhooks->events->list(limit: 20);
$client->webhooks->events->replay('evt_1');
```

Responses are typed, immutable models (`$pan->registeredName`). Fields the SDK does not know about are never dropped:
`$pan->raw()` returns the full decoded body and `$pan->get('field')` reads any field. HTTP metadata
(status, headers, request id, attempts) is available from `$pan->response()`. Endpoints whose schema the API
does not fix yet (e-Stamp reference data) return `GenericResponse` (`->raw()`, `->get()`, `->items()`).

## Errors

All errors extend `StampMitra\Exception\StampMitraError` (`getStatus()`, `getErrorCode()`, `getMessage()`,
`getRequestId()`, `getField()`, `getBody()`):

| HTTP | Exception |
|------|-----------|
| 400 (and local validation) | `ValidationError` |
| 401 | `AuthenticationError` |
| 402 | `PaymentRequiredError` |
| 403 | `PermissionError` (server message verbatim, e.g. IP allowlist) |
| 404 | `NotFoundError` |
| 409 | `ConflictError` |
| 429 | `RateLimitError` (`getRetryAfter()` when the header is present) |
| 5xx | `UpstreamError` |
| network / timeout | `ConnectionError` / `TimeoutError` |

Other statuses raise the base `StampMitraError`; non-JSON error bodies still map by status.

```php
try {
    $client->verify->gstin('27ABCDE1234F1Z5');
} catch (\StampMitra\Exception\RateLimitError $e) {
    // back off
} catch (\StampMitra\Exception\StampMitraError $e) {
    error_log($e->getRequestId() . ': ' . $e->getMessage());
}
```

## Retries and idempotency

Default `maxRetries` is 2 with exponential backoff and full jitter (0.5s base, 8s cap); `Retry-After` is honoured when sent.
Retried: 429, 502, 503, 504, connection errors and timeouts. GET is always retry-safe. A POST is retried only when it carries an
`Idempotency-Key`. The SDK generates a UUIDv4 key for every POST the API documents the header for (verification, duty calculation,
e-Stamp parties, orders, payment intents) and re-uses the **same key across retries**; pass your own with
`['idempotencyKey' => '...']`. Other POSTs (webhook replay) are never retried unless you supply a key.

Timeouts: with Guzzle the `timeout` option is applied per request. With another PSR-18 client, configure the timeout on that client.
PHP has no cancellation token, so there is no signal option.

## Pagination

```php
foreach ($client->logs->iterate(['service' => 'pan.verify', 'limit' => 100]) as $entry) {
    echo $entry->id, ' ', $entry->createdAt, PHP_EOL;   // follows nextCursor lazily (a Generator)
}
$page = $client->logs->list(['limit' => 50]);          // $page->items, $page->nextCursor
```

## Webhooks

Deliveries carry `X-Webhook-Signature`, `X-Webhook-Timestamp` (unix seconds) and `X-Webhook-Event-Id`.
The signature is `hex(HMAC-SHA256(secret, "{timestamp}.{rawBody}"))` over the body **exactly as received**.

```php
use StampMitra\Webhook;

$payload = file_get_contents('php://input');           // raw bytes, do not re-encode
$event = Webhook::constructEvent($payload, $_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'], $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'], $secret);
// or: Webhook::verifySignature($payload, $timestamp, $signature, $secret, toleranceSeconds: 300): bool
// or: Webhook::constructEventFromHeaders($payload, $headers, $secret)
```

Comparison is constant-time; timestamps more than 300s (configurable) from now are rejected; failures raise
`SignatureVerificationError` from `constructEvent`. See `examples/plain_webhook.php` and `examples/laravel_webhook.php`.

## Validation

Inputs are checked before sending (PAN, GSTIN, IFSC, CIN, Udyam, vehicle number, 9-18 digit account number, passport file number,
driving licence, voter ID, phone, ISO dates), normalised to upper case, with a local `ValidationError` and no network call or billing.
Disable with `validateInput: false`.

## Logging

Pass any PSR-3 logger as `logger:` to get debug logs of requests/responses. `Authorization`/`x-api-key` are redacted and identifiers
and names in bodies are masked. Nothing is logged by default.

## Development

```bash
composer install && composer test && composer stan && composer cs && composer coverage:spec
```

`spec/openapi.json` is a snapshot of the public spec; `composer coverage:spec` (and a unit test) fail if any `METHOD + path`
in it has no SDK method. See [CONTRIBUTING.md](CONTRIBUTING.md), [RELEASING.md](RELEASING.md), [SECURITY.md](SECURITY.md).

MIT licensed. Copyright StampMitra / Bani Global Industries LLP.
