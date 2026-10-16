# Contributing

Thanks for helping improve the StampMitra PHP SDK.

## Setup

```bash
composer install
composer test          # PHPUnit 10
composer stan          # PHPStan level 8
composer cs            # PHP-CS-Fixer dry run (PSR-12); composer cs:fix to apply
composer coverage:spec # every operation in spec/openapi.json must have an SDK method
```

Conformance run (all 31 operations, error mapping, retries, idempotency, timeouts, pagination, shared fixtures) against the
spec repo's mock server:

```bash
git clone https://github.com/Bani-Global-Industries-LLP/stampmitra-developer-openapi ../stampmitra-developer-openapi
node ../stampmitra-developer-openapi/scripts/mock-server.mjs --port 4010 &
MOCK_SERVER_URL=http://127.0.0.1:4010 composer test:conformance   # skipped when MOCK_SERVER_URL is unset; CI sets CONFORMANCE_REQUIRED=1
```

`tests/fixtures/*.json` are copies of the shared fixtures in the spec repo: refresh them when it changes.

### Restricted networks

Composer downloads dists from `api.github.com`. If that host is blocked (some sandboxes, corporate proxies), `composer install`
fails with "Could not authenticate against github.com". Workarounds: set a token (`composer config --global github-oauth.github.com <token>`),
use a mirror, or install everything except PHPStan with `composer install --prefer-source` from a copy of `composer.json` without
`phpstan/phpstan` (`COMPOSER=composer-local.json`, git-ignored) and run PHPStan from a downloaded `phpstan.phar`:
`php phpstan.phar analyse`. CI is unaffected and retries `composer install` three times.

PHP 8.1+ is supported: no `readonly` classes or other 8.2+ syntax.

## Guidelines

- Keep `declare(strict_types=1);` in every file and add tests for every change.
- Never log or commit real keys or personal data; tests use fake clients, never the network.
- `spec/openapi.json` is a snapshot of the public spec. When the API changes, refresh it and add the new SDK methods; the coverage test will fail until you do.
- Update `CHANGELOG.md` under *Unreleased*.
- By contributing you agree your work is licensed under the MIT license.

Security issues: see [SECURITY.md](SECURITY.md), do not open a public issue.
