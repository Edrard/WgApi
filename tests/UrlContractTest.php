<?php

declare(strict_types=1);

namespace edrard\Tests\WgApi;

use edrard\WgApi\GetWgApi;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UrlContractTest extends TestCase
{
    public function testBuildsExactlyOneUrlAndPreservesAllCallerParameters(): void
    {
        $api = new GetWgApi(['eu' => 'configured-id']);
        $url = $api->getUrl('eu', 'wot', 'account/info', [
            'account_id' => [1, 2, 3],
            'fields' => ['nickname', 'statistics.all'],
            'extra' => ['statistics.random'],
            'language' => 'uk',
            'access_token' => 'private-token',
            'application_id' => 'cannot-override',
        ]);

        self::assertSame('https://api.worldoftanks.eu/wot/account/info/', strtok($url, '?'));
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        self::assertSame('1,2,3', $query['account_id']);
        self::assertSame('nickname,statistics.all', $query['fields']);
        self::assertSame('statistics.random', $query['extra']);
        self::assertSame('uk', $query['language']);
        self::assertSame('private-token', $query['access_token']);
        self::assertSame('configured-id', $query['application_id']);
    }

    public function testDoesNotInventLanguageOrSplitIdentifiers(): void
    {
        $url = (new GetWgApi(['na' => 'id']))->getUrl('na', 'wot', 'account/info', ['account_id' => range(1, 250)]);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertArrayNotHasKey('language', $query);
        self::assertCount(250, explode(',', $query['account_id']));
    }

    public function testRejectsInvalidOrigin(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new GetWgApi(['eu' => 'id'], ['eu' => 'http://example.test']);
    }

    public function testRejectsInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new GetWgApi(['eu' => 'id']))->getUrl('eu', 'wot', '../account/info');
    }

    public function testDebugOutputHidesConfiguredId(): void
    {
        $api = new GetWgApi(['eu' => 'private-application-id']);
        ob_start();
        var_dump($api);
        $output = (string) ob_get_clean();

        self::assertStringNotContainsString('private-application-id', $output);
    }

    public function testRejectsObjectQueryValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new GetWgApi(['eu' => 'id']))->getUrl('eu', 'wot', 'account/info', ['extra' => new \stdClass()]);
    }
}
