# Changelog

## Unreleased — planned 2.0.1

- Preserve authenticated HTTPS GET support and protect query/extra arguments in exception traces with SensitiveParameter.
- Reject control characters and backslashes in configured origins and mark query/extra arguments sensitive in stack traces.
- Add security regression tests and PHP 8.5 CI with PSR-12, PHPStan, Composer and dependency audit checks.

## 2.0.0 — 2026-09-27

Major migration from the legacy PHP 5.4 code; requires PHP 8.5.

- Add typed URL-builder contracts, canonical EU/NA/ASIA realms and validated HTTPS configuration.
- Require explicit application IDs; remove bundled IDs and obsolete helper/logging dependencies.
- Support arbitrary public GET paths and registered batch endpoints with documented WG limits.
- Validate IDs, de-duplicate batches and encode query parameters with RFC 3986 rules.
- Keep existing player wrapper names and EU/NA/ASIA aliases; reject the unsupported RU realm.
- Add README examples, MIT license, PHPUnit, PHPStan level 6 and PSR-12 checks.

Validation: 28 tests / 264 assertions; live public WoT method smoke checks passed in three realms. All WG endpoints and private operations have not been verified.
