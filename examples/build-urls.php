<?php

declare(strict_types=1);

use edrard\WgApi\Endpoint;
use edrard\WgApi\GetWgApi;

require dirname(__DIR__).'/vendor/autoload.php';

// Offline example: replace the placeholder through application configuration.
$id = getenv('WG_APPLICATION_ID') ?: 'example-application';
$api = new GetWgApi(array_fill_keys(['eu', 'na', 'asia'], $id));
$api->changeUrlPrefix('stats_');
$stats = $api->getPlayerStat('eu', [500000001, 500000002], [], ['fields' => ['account_id', 'statistics.all']]);
$catalog = $api->getUrl('asia', 'wot', 'encyclopedia/vehicles', ['limit' => 1, 'fields' => 'tank_id']);
$api->registerEndpoint('clanInfo', new Endpoint('clans/info', 'clan_id', 100));
$api->changeUrlPrefix('clans_');
$clans = $api->prepareBatch('clanInfo', 'na', [1, 2], extra: ['fields' => 'clan_id']);
// URLs contain application IDs; print only harmless metadata.
echo json_encode(['stats_requests' => count($stats), 'catalog_host' => parse_url($catalog, PHP_URL_HOST), 'clan_requests' => count($clans)], JSON_THROW_ON_ERROR)."\n";
