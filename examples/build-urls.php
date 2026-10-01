<?php

declare(strict_types=1);

use edrard\WgApi\GetWgApi;

require dirname(__DIR__).'/vendor/autoload.php';

$id = getenv('WG_APPLICATION_ID') ?: 'example-application';
$api = new GetWgApi(['eu' => $id]);
$urls = [];
foreach (array_chunk(range(500000001, 500000250), 25) as $ids) {
    $urls[] = $api->getUrl('eu', 'wot', 'account/info', ['account_id' => $ids, 'fields' => ['account_id']]);
}
// URLs contain the application ID; print only metadata.
echo json_encode(['url_count' => count($urls), 'host' => parse_url($urls[0], PHP_URL_HOST)], JSON_THROW_ON_ERROR).PHP_EOL;
