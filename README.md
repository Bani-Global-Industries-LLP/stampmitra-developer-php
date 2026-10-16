# StampMitra PHP SDK

[![CI](https://github.com/Bani-Global-Industries-LLP/stampmitra-developer-php/actions/workflows/ci.yml/badge.svg)](https://github.com/Bani-Global-Industries-LLP/stampmitra-developer-php/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/stampmitra/stampmitra-php)](https://packagist.org/packages/stampmitra/stampmitra-php)
[![PHP](https://img.shields.io/packagist/dependency-v/stampmitra/stampmitra-php/php)](composer.json)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

> The Packagist badges show a version only once the package is published.

Official PHP client for the [StampMitra Developer API](https://developer.stampmitra.in/docs):
Indian identity verification (PAN, GSTIN, vehicle RC, Voter ID, driving licence, passport, CIN, Udyam, bank account)
and e-Stamp Paper. OpenAPI spec: <https://developer.stampmitra.in/openapi.json>.

> **Status: pre-1.0 (0.1.0).** The API surface may change in minor releases. Pin your version.

SDK guide: <https://developer.stampmitra.in/sdks/php> | API docs: <https://developer.stampmitra.in/docs>

## Install

```bash
composer require stampmitra/stampmitra-php guzzlehttp/guzzle
```

Requires PHP 8.1 or newer (tested on 8.1 to 8.4) and `ext-json`. The SDK talks PSR-18/PSR-17 and discovers
whichever client and factories are installed. Guzzle is the recommended client; any other works too:

```bash
composer require stampmitra/stampmitra-php symfony/http-client nyholm/psr7 php-http/discovery
# or pass your own client: new StampMitra(httpClient: $psr18Client, requestFactory: ..., streamFactory: ...)
```

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
`appInfo: ['name'=>..,'version'=>..,'url'=>..]`, `logger` (PSR-3), `validateInput` (true), `allowCustomKey`,
`requestFactory`, `streamFactory`. `baseUrl` must be `https://` (plain `http://` is accepted only for localhost, e.g. a mock server).
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
`Retry-After` waits are capped at 60s. Retried: 429, 502, 503, 504, connection errors and timeouts. GET is always retry-safe. A POST is retried only when it carries an
`Idempotency-Key`. The SDK generates a UUIDv4 key for every POST the API documents the header for (verification, duty calculation,
e-Stamp parties, orders, payment intents) and re-uses the **same key across retries**; pass your own with
`['idempotencyKey' => '...']`. Other POSTs (webhook replay) are never retried unless you supply a key.

### Timeouts

`timeout` (default 30s, per attempt) is enforced per call **only when the HTTP client is Guzzle**: the SDK passes it to every
request, so `['timeout' => 5]` on a single call works. **PSR-18 has no per-request timeout**, so with any other client
(Symfony HttpClient, a custom client, ...) the `timeout` and per-call `timeout` options are ignored: configure the timeout on
that client yourself (e.g. `HttpClient::create(['timeout' => 5])` for Symfony). A timed-out request raises `TimeoutError` and is retried
like any connection error when retry-safe. PHP has no cancellation token, so there is no signal option.

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

## Pricing

The SDK is free and MIT-licensed. You pay only for successful API calls, from one prepaid wallet. Sandbox is free: mock data on every endpoint, and it never debits your wallet.

Per-call rates by plan, in rupees, excluding 18% GST:

| Product | Pay-as-you-go | Growth | Scale | Startup | Enterprise |
|---|---|---|---|---|---|
| Standard verification (PAN, GSTIN, Voter ID, Driving Licence, CIN, Udyam, cross-lookups) | ₹5 | ₹4 | ₹3.50 | ₹4 | Custom |
| Passport and Vehicle RC | ₹9 | ₹8.50 | ₹8 | ₹8.50 | Custom |
| Bank account (penny-drop) | ₹4 | ₹4 | ₹4 | ₹4 | Custom |
| e-Stamp API fee (StampMitra's charge only) | ₹5 | ₹4 | ₹3 | ₹4 | Custom |

| | Sandbox | Pay-as-you-go | Growth | Scale | Startup | Enterprise |
|---|---|---|---|---|---|---|
| Plan fee | Free | ₹0 / month | ₹999 / month | ₹4,999 / month | Free for 6 months, then 50% off for 6 | Custom |
| Seats / API keys / webhooks | 1 / 2 / 1 | 3 / 5 / 3 | 10 / 20 / 10 | 25 / 50 / 25 | 10 / 20 / 10 | Custom |
| Requests per minute | 20 | 60 | 300 | 1,000 | 300 | Custom |
| Log retention | 7 days | 30 days | 90 days | 365 days | 90 days | Custom |
| IP allowlisting | No | No | Yes | Yes | Yes | Yes |
| White-label and custom domain | No | No | No | Yes | No | Yes |

Growth and Scale are also available yearly (₹9,990 and ₹49,990, two months free). There are separate Startup and NGO programmes. Plans, rates and limits can change, and [developer.stampmitra.in/pricing](https://developer.stampmitra.in/pricing) is the source of truth; see it for how an e-Stamp order is priced in total.

## Security

- Use keys server-side only and store them in environment variables or a secret manager; never commit them.
- TLS verification is never disabled by the SDK, redirects are not followed (so credentials cannot leak to another host), and
  `baseUrl` must be `https://` except for localhost.
- The API key is never included in exception messages, logs or `var_dump`/`print_r` of the client.
- Always verify webhook signatures on the raw body and keep the default 300s timestamp tolerance.
- Report vulnerabilities as described in [SECURITY.md](SECURITY.md), not in public issues.

## Supported versions

PHP 8.1, 8.2, 8.3 and 8.4 (CI matrix). `psr/http-client` ^1, `psr/http-factory` ^1, `psr/http-message` ^1.1 or ^2, `psr/log` ^1 to ^3.
Only the latest minor release receives fixes while the SDK is pre-1.0. See [CHANGELOG.md](CHANGELOG.md).

## Development

```bash
composer install && composer test && composer stan && composer cs && composer coverage:spec
# black-box run against the spec repo's mock server (see CONTRIBUTING.md)
MOCK_SERVER_URL=http://127.0.0.1:4010 composer test:conformance
```

`spec/openapi.json` is a snapshot of the public spec; `composer coverage:spec` (and a unit test) fail if any `METHOD + path`
in it has no SDK method. See [CONTRIBUTING.md](CONTRIBUTING.md), [RELEASING.md](RELEASING.md), [SECURITY.md](SECURITY.md).

Licensed under the [MIT License](LICENSE). Copyright Bani Global Industries LLP (StampMitra).
