# WgApi

PHP 8.5 library that builds **one World of Tanks GET URL** from a realm, API path and already prepared parameters. It performs no HTTP requests, retry, ID splitting, response parsing, or rate control.

The next breaking release is **3.0.0 (unreleased worktree)**. The API namespace used in URLs is `wot`; this is not a numeric WG API version. The reviewed endpoint catalog in WotClient is dated 2026-09-27.

```php
use edrard\WgApi\GetWgApi;

$api = new GetWgApi(['eu' => $applicationId]);
$url = $api->getUrl('eu', 'wot', 'account/info', [
    'account_id' => [500000001, 500000002],
    'fields' => ['account_id', 'nickname'],
    'extra' => ['statistics.random'],
    'language' => 'de',
]);
// One URL for exactly these two IDs. WgApi never creates another URL.
```

`application_id` is always taken from configuration and cannot be overridden in query parameters. You may configure separate IDs for `eu`, `na`, and `asia`, or explicitly supply the same ID for each. Historical `us` and `sea` realm aliases resolve to `na` and `asia`. A base origin can be overridden only with a trusted HTTPS origin:

```php
$api = new GetWgApi(
    ['eu' => $euId, 'na' => $naId, 'asia' => $asiaId],
    baseUrls: ['eu' => 'https://api.worldoftanks.eu'],
);
```

Parameter arrays become comma-separated values, null parameters are omitted, and query strings use RFC 3986 encoding. The builder does **not** add `language` implicitly; the caller passes it for methods that support it. `fields`, `extra`, `access_token`, and other method parameters are forwarded without interpreting their meaning. Invalid paths, parameter shapes, realm names, and base origins raise `InvalidArgumentException` before a URL is returned. A missing application ID for a selected realm raises `LogicException`.

For a 250-ID request with K=25, the caller produces ten 25-ID groups and calls `getUrl()` ten times. WgApi does not know N, K, HTTP quotas, or whether the response succeeds. Keep token-bearing URLs out of logs and traces.

MIT license. Package name: `edrard/wgapi`. After the 3.0.0 tag is published, consumers should use `^3.0`. Source: [Edrard/WgApi](https://github.com/Edrard/WgApi). Authentication POST operations belong to the separate [WgAuth](https://github.com/Edrard/WgAuth) package.

Development checks: `composer test`, `composer analyse`, `composer format:check`, `composer validate --strict` on PHP 8.5.

## Public API

Load Composer's `vendor/autoload.php` before using these classes. The package declares PHP `^8.5`, `ext-ctype` and `ext-filter`. Examples use an application ID supplied by your application.

All classes below are in `edrard\WgApi`. Configuration and the builder are immutable.

| Class / interface | Public operation | Result |
| --- | --- | --- |
| `GetWgApi` | `__construct(array $applicationIds, array $baseUrls = [])` | Configures IDs and optional origins by realm. |
| `UrlBuilderInterface`, implemented by `GetWgApi` | `getUrl(string $server, string $type, string $target, array $parameters = []): string` | One URL; no endpoint catalog validation or network I/O. |
| `ApiConfiguration` | `__construct(array $applicationIds, array $baseUrls = [])` | Independently usable configuration with the same validation. |
| `ApiConfiguration` | `applicationId(Realm $realm): string` | Configured ID; throws if absent. |
| `ApiConfiguration` | `baseUrl(Realm $realm): string` | Configured or default HTTPS origin. |
| `Realm` | `resolve(string $realm): Realm` | Case-insensitive realm or historical alias; surrounding whitespace is not accepted. |

`Realm` has `EU` (`eu`), `NA` (`na`) and `ASIA` (`asia`) cases. Default origins are `https://api.worldoftanks.eu`, `https://api.worldoftanks.com` and `https://api.worldoftanks.asia` respectively. IDs need only be non-empty strings; this builder does not verify their validity with WG. Paths consist of lowercase letters, digits and underscores, with `/` between segments. Supply `wot` and `account/info`, without leading or trailing slashes.

Parameters must have non-empty string names. Values may be scalars, null, or lists of scalars; objects and nested or associative arrays are rejected. An empty list becomes an empty query value. Scalar booleans use PHP query encoding (`1`/`0`); list values use PHP comma joining. Parameter insertion order is retained and the configured `application_id` is appended last.

For example, with application ID `example-app`, this call:

```php
$url = (new GetWgApi(['eu' => 'example-app']))->getUrl(
    'eu', 'wot', 'account/info', ['account_id' => [1, 2], 'language' => 'de'],
);
```

returns exactly:

```text
https://api.worldoftanks.eu/wot/account/info/?account_id=1%2C2&language=de&application_id=example-app
```

Additional parameters are not filtered against WG documentation; validation of endpoint-specific names and values belongs to the caller. `__debugInfo()` redacts configuration on both classes, but the returned URL contains the actual parameters.

After publication, install with `composer require edrard/wgapi:^3.0`. Before publication, use a root Composer path repository pointing to this checkout with version `3.0.x-dev` and an explicit `^3.0@dev` requirement. Composer repository declarations belong in the consuming project's root configuration. See the three-package [local setup example](../WotClient/README.md#installation).

The maintained executable example is `php examples/build-urls.php`; it needs no network and prints URL count and host. Files in the older singular `example/` directory are historical examples for obsolete interfaces and are not compatible with 3.x.
