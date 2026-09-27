# WgApi — PHP 8.5

Validated Wargaming World of Tanks URL and batch builder. This package does not perform network requests.

## Setup

PHP 8.5 and Composer 2 are required.

```sh
composer install
composer test
composer analyse
composer format:check
```

```php
use edrard\WgApi\GetWgApi;

$api = new GetWgApi(['eu' => $applicationId]);
$api->changeUrlPrefix('stats_');
$urls = $api->getPlayerStat('eu', [500000001, 500000002], [], [
    'fields' => ['account_id', 'nickname', 'statistics.all'],
]);
```

Application IDs must come from the consuming application's configuration. No IDs are bundled. The second constructor argument supports partial overrides of id, url, lang and start. Base URLs must be HTTPS origins. changeIds() updates only the supplied realms.

## Contract and migration

- Realms: eu, na, asia. Legacy us and sea aliases remain supported. ru is rejected rather than silently routed to another provider.
- Existing getPlayerId, getPlayerStat, getPlayerTankStat, getPlayerTankStatFull and getPlayerAchiv names remain.
- account/info, account/tanks and account/achievements accept up to 100 accounts per URL; tanks/stats accepts one. Nickname search uses one search string per URL.
- Results are keyed by prefix + index. Empty input produces no URLs; duplicate IDs are removed. IDs must be positive integers within the platform integer range.
- max is a positive integer or false for the endpoint limit. Query strings use RFC 3986 encoding; array values become comma-separated lists.
- Explicit batch IDs and configured application IDs take precedence over conflicting extra parameters.
- addServerBaseId() applies only to relative enumeration IDs. Never use it on absolute IDs received from WG.
- getUrl() supports additional API paths but does not validate endpoint-specific parameters. For another game's API, also configure its matching base origins.
- Register batch strategies with registerEndpoint() and Endpoint. Endpoints use numeric IDs by default; set numeric: false for string searches. UrlBuilderInterface is the consumer boundary.
- fullLog() is a deprecated no-op. URL generation never creates log files.

Release: v2.0.0. This is a major-version migration, not a drop-in PHP 5.4 replacement. The Composer name is edrard/wgapi; use ^2.0 for stable releases. The development branch alias is 2.0.x-dev.

Source: [Edrard/WgApi](https://github.com/Edrard/WgApi), Edrard, MIT. The new Laravel application has not adopted this package yet. Shared review and migration evidence: Docs/Reports/WG-LIBS-001_2026-09-26_review.md, relative to the shared workspace root.

Official contracts: [account/info](https://developers.wargaming.net/reference/all/wot/account/info/), [account/tanks](https://developers.wargaming.net/reference/all/wot/account/tanks/), [account/achievements](https://developers.wargaming.net/reference/all/wot/account/achievements/), [tanks/stats](https://developers.wargaming.net/reference/all/wot/tanks/stats/).
## Complete offline example

```sh
php examples/build-urls.php
```

This builds URLs and prints only request counts and the selected host. It performs no network requests and uses a harmless placeholder unless WG_APPLICATION_ID is configured.

A single shared application ID can be configured for all three realms explicitly:

```php
require __DIR__.'/vendor/autoload.php';

use edrard\WgApi\Endpoint;
use edrard\WgApi\GetWgApi;

$id = getenv('WG_APPLICATION_ID') ?: throw new LogicException('Configure WG_APPLICATION_ID.');
$api = new GetWgApi(array_fill_keys(['eu', 'na', 'asia'], $id));
```

### Any additional public GET method

Pass the namespace, method path and parameters from the official reference; no new wrapper class is needed:

```php
$infoUrl = $api->getUrl('eu', 'wot', 'encyclopedia/info', [
    'fields' => 'tanks_updated_at',
]);
$vehicleUrl = $api->getUrl('asia', 'wot', 'encyclopedia/vehicles', [
    'page_no' => 1, 'limit' => 100,
    'fields' => ['tank_id', 'name', 'tier'],
]);
$clanUrl = $api->getUrl('na', 'wot', 'clans/list', [
    'limit' => 10, 'fields' => 'clan_id,tag',
]);
```

The generic builder validates the URL/path, realm, ID configuration and parameter value shapes. It does not know whether a method exists, requires POST/authentication or supports the fields/parameters you supply. WG validates those contracts. WgAuth provides authentication POST operations; authenticated HTTPS GET URLs are also supported by this builder. Redact access_token parameters in logs when using them.

### Add a batch endpoint

```php
$api->registerEndpoint('clanInfo', new Endpoint(
    path: 'clans/info',
    parameter: 'clan_id',
    limit: 100,
));
$api->changeUrlPrefix('clans_');
$urls = $api->prepareBatch('clanInfo', 'eu', [1, 2, 3], extra: [
    'fields' => ['clan_id', 'tag'],
], max: 50);
// Request keys: clans_0, clans_1, ...; IDs are grouped by the configured limit.
```

### Existing wrapper map

| Method | WG path | IDs per request |
| --- | --- | --- |
| getPlayerId | account/list | One search string |
| getPlayerStat | account/info | Up to 100 |
| getPlayerTankStat | account/tanks | Up to 100 |
| getPlayerAchiv | account/achievements | Up to 100 |
| getPlayerTankStatFull | tanks/stats | One account |

For player wrappers, the third argument is a list for WG's extra parameter, while ordinary query parameters go in the fourth argument. getPlayerId instead takes query parameters as its third argument:

```php
$search = $api->getPlayerId('eu', ['tank'], ['limit' => 1, 'fields' => 'account_id']);
$stats = $api->getPlayerStat('eu', [500000001], ['statistics.random'], [
    'fields' => 'account_id,statistics.random',
]);
```

Consume these URL maps with WgDataGetter or your own HTTP client. Do not log URLs containing application IDs or credentials.

Authenticated HTTPS GET URLs remain supported, including access_token parameters. Query arguments are marked SensitiveParameter for PHP exception traces; callers must also redact token-bearing URLs in their own logs. WotClient and WgAuth use POST for their token-bearing operations. Configured API origins must come from trusted application configuration; endpoint paths cannot change the origin.

## Live verification scope

On 2026-09-26 one owner-provided ID passed eight public methods in EU, NA and ASIA: account/list, account/info, account/tanks, account/achievements, tanks/stats, encyclopedia/info, encyclopedia/vehicles and clans/list. This checks all five wrappers and three additional generic GET paths. It does not verify every endpoint, every game, private data or historical external sources. The same tested ID worked in all three realms; other application IDs may have different restrictions.
