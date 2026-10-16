# Releasing

Releases are cut by pushing a `vX.Y.Z` tag. Packagist picks the tag up from
GitHub; **no token or secret is stored in this repository or in the workflow.**

## One-time setup (maintainer)

1. Sign in at https://packagist.org with the organisation account and submit
   `https://github.com/Bani-Global-Industries-LLP/stampmitra-developer-php`
   as package `stampmitra/stampmitra-php`.
2. Enable automatic updates: either install the **Packagist GitHub app** on the
   repository (recommended), or add a GitHub webhook
   (Settings > Webhooks) pointing at `https://packagist.org/api/github?username=<packagist-user>`
   with content type `application/json`, and the secret shown on your Packagist profile
   (the secret lives only in GitHub's webhook settings).
3. Optionally protect the `v*` tag pattern in GitHub (Settings > Rules).

Until this is done the release workflow still builds, tests and creates a GitHub
release; Packagist simply will not know about the package yet.

## Per release

1. Move the *Unreleased* notes into a new section in `CHANGELOG.md`.
2. Update `StampMitra::VERSION` in `src/StampMitra.php` (the workflow fails if it does not match the tag).
3. `git tag v0.1.0 && git push origin v0.1.0`.
4. The `Release` workflow runs the full test suite and creates the GitHub release; Packagist updates through the webhook / app.

`composer.json` intentionally has no `version` field: Packagist derives it from the tag.
