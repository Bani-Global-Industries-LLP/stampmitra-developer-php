# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project adheres
to [Semantic Versioning](https://semver.org/) (pre-1.0: minor versions may break).

## [Unreleased]

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

[Unreleased]: https://github.com/Bani-Global-Industries-LLP/stampmitra-developer-php/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/Bani-Global-Industries-LLP/stampmitra-developer-php/releases/tag/v0.1.0
