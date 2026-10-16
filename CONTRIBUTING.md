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

PHP 8.1+ is supported: no `readonly` classes or other 8.2+ syntax.

## Guidelines

- Keep `declare(strict_types=1);` in every file and add tests for every change.
- Never log or commit real keys or personal data; tests use fake clients, never the network.
- `spec/openapi.json` is a snapshot of the public spec. When the API changes, refresh it and add the new SDK methods; the coverage test will fail until you do.
- Update `CHANGELOG.md` under *Unreleased*.
- By contributing you agree your work is licensed under the MIT license.

Security issues: see [SECURITY.md](SECURITY.md), do not open a public issue.
