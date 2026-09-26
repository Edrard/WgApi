<?php

declare(strict_types=1);

namespace edrard\Tests\WgApi;

use edrard\WgApi\Endpoint;
use edrard\WgApi\GetWgApi;
use edrard\WgApi\Realm;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GetWgApiTest extends TestCase
{
    private function api(): GetWgApi
    {
        return new GetWgApi(['eu' => 'test-app', 'na' => 'test-na', 'asia' => 'test-asia']);
    }
    public function testQueryEncodingAndReservedId(): void
    {
        $url = $this->api()->getUrl('eu', 'wot', 'account/list', ['search' => 'Name & +/Ю', 'fields' => ['nickname', 'account_id'], 'application_id' => 'wrong']);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        self::assertSame('Name & +/Ю', $query['search']);
        self::assertSame('nickname,account_id', $query['fields']);
        self::assertSame('test-app', $query['application_id']);
        self::assertStringNotContainsString(' ', $url);
        self::assertStringStartsWith('https://api.worldoftanks.eu/wot/account/list/?', $url);
    }
    public static function realms(): array
    {
        return [['eu', 'api.worldoftanks.eu', Realm::EU], ['na', 'api.worldoftanks.com', Realm::NA], ['us', 'api.worldoftanks.com', Realm::NA], ['asia', 'api.worldoftanks.asia', Realm::ASIA], ['sea', 'api.worldoftanks.asia', Realm::ASIA]];
    }
    #[DataProvider('realms')]
    public function testRealmAliases(string $realm, string $host, Realm $canonical): void
    {
        self::assertSame($canonical, Realm::resolve($realm));
        self::assertSame($host, parse_url($this->api()->getPlayerStat($realm, [1])[0], PHP_URL_HOST));
    }
    public static function methods(): array
    {
        return [['getPlayerStat', 100], ['getPlayerTankStat', 100], ['getPlayerAchiv', 100], ['getPlayerTankStatFull', 1]];
    }
    #[DataProvider('methods')]
    public function testMethodLimits(string $method, int $limit): void
    {
        $api = $this->api();
        $api->changeUrlPrefix('stats_');
        $urls = $api->$method('eu', range(1, 101), [], ['account_id' => 'override'], 1000);
        self::assertCount((int) ceil(101 / $limit), $urls);
        self::assertArrayHasKey('stats_0', $urls);
        $seen = [];
        foreach ($urls as $url) {
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $ids = explode(',', $query['account_id']);
            self::assertLessThanOrEqual($limit, count($ids));
            array_push($seen, ...$ids);
        }
        self::assertSame(array_map('strval', range(1, 101)), $seen);
    }
    public function testPartialConfigurationAndIdUpdates(): void
    {
        $api = new GetWgApi(['eu' => 'first', 'us' => 'na'], ['lang' => ['eu' => 'pl']]);
        $api->changeIds(['eu' => 'second']);
        parse_str(parse_url($api->getPlayerStat('us', [1])[0], PHP_URL_QUERY), $na);
        self::assertSame('na', $na['application_id']);
        parse_str(parse_url($api->getPlayerStat('eu', [1])[0], PHP_URL_QUERY), $eu);
        self::assertSame('pl', $eu['language']);
        self::assertSame('second', $eu['application_id']);
    }
    public function testEmptyAndDuplicateIds(): void
    {
        self::assertSame([], $this->api()->getPlayerStat('eu', []));
        parse_str(parse_url($this->api()->getPlayerStat('eu', [1, 1, 2])[0], PHP_URL_QUERY), $query);
        self::assertSame('1,2', $query['account_id']);
    }
    public function testSearchIsOneNamePerUrl(): void
    {
        $urls = $this->api()->getPlayerId('eu', ['Alice', 'Bob']);
        self::assertCount(2, $urls);
        parse_str(parse_url($urls[1], PHP_URL_QUERY), $query);
        self::assertSame('Bob', $query['search']);
    }
    public function testExplicitBatchSizeAndExtraFields(): void
    {
        $urls = $this->api()->getPlayerStat('eu', [1, 2, 3], ['statistics.random'], [], 2);
        self::assertCount(2, $urls);
        parse_str(parse_url($urls[0], PHP_URL_QUERY), $query);
        self::assertSame('statistics.random', $query['extra']);
    }
    public function testCustomEndpointAndGame(): void
    {
        $api = $this->api();
        $api->registerEndpoint('clans', new Endpoint('clans/info', 'clan_id', 1, 'wgn'));
        self::assertStringContainsString('/wgn/clans/info/', $api->prepareBatch('clans', 'eu', [123])[0]);
    }
    public static function invalidBatches(): array
    {
        return [[0, [1]], [-2, [1]], [false, [0]], [false, [-1]], [false, ['1&x=2']], [false, [1.5]]];
    }
    #[DataProvider('invalidBatches')]
    public function testInvalidBatch(int|false $max, array $ids): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->api()->getPlayerStat('eu', $ids, [], [], $max);
    }
    public function testMissingApplicationIdFailsClearly(): void
    {
        $this->expectException(LogicException::class);
        (new GetWgApi())->getPlayerStat('eu', [1]);
    }
    public function testRuIsNotSilentlyMapped(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->api()->getPlayerStat('ru', [1]);
    }
    public function testPathInjectionIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->api()->getUrl('eu', 'wot', '../auth/logout');
    }
    public function testInsecureOriginIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new GetWgApi([], ['url' => ['eu' => 'http://example.test']]);
    }
    public function testAccountOffset(): void
    {
        $ids = [1, 2];
        $this->api()->addServerBaseId($ids, 'asia');
        self::assertSame([2000000001, 2000000002], $ids);
    }

    public function testInvalidRelativeIdDoesNotPartiallyMutateInput(): void
    {
        $ids = [1, -1];
        try {
            $this->api()->addServerBaseId($ids, 'eu');
            self::fail('Expected invalid relative ID.');
        } catch (InvalidArgumentException) {
            self::assertSame([1, -1], $ids);
        }
    }

    public function testOverflowingAccountIdIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->api()->getPlayerStat('eu', ['999999999999999999999999999']);
    }
}
