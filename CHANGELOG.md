# Changelog

## Unreleased 3.0.0 — 2026-10-01

- Build exactly one URL per call; remove endpoint registration, ID splitting, wrapper methods, key prefixes and relative ID conversion.
- Only inject configured application_id; forward explicitly supplied method parameters without adding language.
- Keep realm and HTTPS origin validation; reject unsafe origin characters and protect URL parameters in traces.
- Add contract and security tests, PHP 8.5 CI, PSR-12, PHPStan, Composer validation and dependency audit checks. This major version has not yet been tagged as a release.

## 2.0.0 — 2026-09-27

Major migration from the legacy PHP 5.4 code; requires PHP 8.5.

- Add typed URL-builder contracts, canonical EU/NA/ASIA realms and validated HTTPS configuration.
- Require explicit application IDs; remove bundled IDs and obsolete helper/logging dependencies.
- Support arbitrary public GET paths and registered batch endpoints with documented WG limits.
- Validate IDs, de-duplicate batches and encode query parameters with RFC 3986 rules.
- Keep existing player wrapper names and EU/NA/ASIA aliases; reject the unsupported RU realm.
- Add README examples, MIT license, PHPUnit, PHPStan level 6 and PSR-12 checks.

Validation: 28 tests / 264 assertions; live public WoT method smoke checks passed in three realms. All WG endpoints and private operations have not been verified.
