# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project adheres
to [Semantic Versioning](https://semver.org/) (pre-1.0: minor versions may break).

## [Unreleased]

## [0.2.1] - 2026-11-25

Patch release: wording and documentation; no API or behaviour changes.

### Changed

- Documentation (README, examples, doc comments) now uses one customer-facing **Service wallet** (the one balance that pays
  verification calls and e-Stamp fees; minimum top-up ₹1,000 plus 18% GST; GST on fees is paid at top-up, and orders debit the
  fees before GST) alongside the **Stamp duty wallet**. `serviceFeeWallet` and the `SERVICE_FEE` / `STAMP_DUTY` values of
  `details.wallet` keep their names (kept for compatibility).
- Documentation: an order can be placed without `panCardImage`, but a PAN card is mandatory to complete the stamp. SDK
  validation and the `PAN_CARD_RECOMMENDED` advisory are unchanged.
- Documentation: e-Stamp pricing per paper (₹99 procurement + ₹5 API charge + convenience fee, by default 5% of the paper's
  duty above ₹1,000; duty multiplied by quantity). Sandbox quotes use the same default rates as live. New accounts get no
  signup credit.

## [0.2.0] - 2026-11-24

Tracks API contract 1.2.0 (62 operations, was 31).

### Added
- e-Stamp catalogue: `estamp->getStatesAvailability()`, `getState()`, `getStateRequirements()` (with `exampleBody`), `listDistricts()`, `listSros()`.
- e-Stamp orders `estamp->orders`: `validate()`, `create()` (Idempotency-Key auto-generated), `list()`, `get()`, `cancel()`, `export()`, `listStamps()`, `uploadDocument()` (multipart PAN card upload; PDF, JPEG or PNG, up to 10 MB).
- Stamps `estamp->stamps`: `get()`, `cancel()`, `replace()`, `setPanCard()` (202), `getCertificate()`.
- Wallets: `estamp->getWallet()`, `stampDutyWallet` and `serviceFeeWallet` (`balance()`, `topup()`, `listTransactions()`, `listInvoices()`, `getInvoice()`), and `account->balance()` / `account->usage()`.
- Typed models (`EstampOrder`, `EstampStamp`, `EstampValidateResult`, `EstampDocument`, `EstampCertificate`, `StampDutyWalletTopup`, `ServiceFeeWalletTopup`, `WalletInvoice`, ...) and request helpers `EstampStampLine`, `EstampParty`, `EstampPropertyArea`.
- Error accessors on every exception: `getDetails()`, `getFieldErrors()` (`FieldError`: field, code, message, index), `getWallet()` (`STAMP_DUTY` / `SERVICE_FEE` on a 402) and `getRetryAfter()`.
- Tests that replay all 195 fixtures of the new operations (scripted client in `composer test`, mock server in `composer test:conformance`), including multipart, key generation, Retry-After and the never-retry set.

### Changed
- `Retry-After` is honoured on 503 and on 409 `IDEMPOTENCY_IN_PROGRESS` (a request carrying a key is retried), as well as on 429.
- Order cancel, stamp cancel, stamp replace, PAN card link, both wallet top-ups and webhook replay are never auto-retried and never sent with an Idempotency-Key (webhook replay with a caller key used to be retried). `orders->validate()` is retry-safe without a key.
- HTTP 422 raises `ValidationError` (was the base `StampMitraError`).
- Multipart bodies are never written to the debug log.
- Spec snapshot and shared fixtures refreshed to contract 1.2.0.

## [0.1.0] - 2026-10-16

### Added
- Initial release: `verify` (PAN, GSTIN, RC, Voter ID, driving licence, passport, CIN, Udyam, PAN-to-GSTIN, PAN-to-Udyam, bank account), `services`, `estamp`, `orders`, `payments->intents`, `usage`, `logs` (with `iterate()` generator), `webhooks->events`.
- `Webhook::verifySignature` / `constructEvent` (HMAC-SHA256, constant-time, timestamp tolerance).
- Typed immutable response models preserving unknown fields via `raw()`.
- Typed exceptions, safe retries with full-jitter backoff, automatic idempotency keys.
- Client-side input validators, redacting PSR-3 debug logging.
- Bundled OpenAPI snapshot and a spec-coverage check.
- Conformance suite (`composer test:conformance`) against the spec repo's mock server, run in CI.
- `baseUrl` must be https (loopback http allowed); API key and webhook secret are `#[SensitiveParameter]` and hidden from dumps.

[Unreleased]: https://github.com/Bani-Global-Industries-LLP/stampmitra-developer-php/compare/v0.2.1...HEAD
[0.2.1]: https://github.com/Bani-Global-Industries-LLP/stampmitra-developer-php/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/Bani-Global-Industries-LLP/stampmitra-developer-php/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/Bani-Global-Industries-LLP/stampmitra-developer-php/releases/tag/v0.1.0
