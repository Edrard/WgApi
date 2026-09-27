<?php

declare(strict_types=1);

namespace edrard\Tests\WgApi;

use edrard\WgApi\GetWgApi;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SecurityReviewTest extends TestCase
{
    public static function tokenNames(): array
    {
        return [['access_token'], [' access_token'], ['access.token'], ['access_token[]']];
    }

    #[DataProvider('tokenNames')]
    public function testAccessTokenIsPreservedInHttpsUrl(string $name): void
    {
        $url = (new GetWgApi(['eu' => 'fixture']))->getUrl('eu', 'wot', 'account/info', [$name => 'fixture-secret']);
        self::assertSame('https', parse_url($url, PHP_URL_SCHEME));
        self::assertStringContainsString(rawurlencode($name).'=fixture-secret', $url);
    }

    public function testControlCharactersInParameterNamesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new GetWgApi(['eu' => 'fixture']))->getUrl('eu', 'wot', 'account/info', ["access_token\0ignored" => 'fixture-secret']);
    }
}
