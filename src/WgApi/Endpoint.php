<?php

declare(strict_types=1);

namespace edrard\WgApi;

use InvalidArgumentException;

final readonly class Endpoint
{
    public function __construct(public string $path, public string $parameter = 'account_id', public int $limit = 100, public string $api = 'wot', public bool $numeric = true)
    {
        self::validatePath($path);
        self::validatePath($api);
        if ($limit < 1 || !preg_match('/^[a-z_]+$/D', $parameter)) {
            throw new InvalidArgumentException('Invalid endpoint parameter or limit.');
        }
    }
    public static function validatePath(string $path): void
    {
        if (!preg_match('~^[a-z0-9_]+(?:/[a-z0-9_]+)*$~D', $path)) {
            throw new InvalidArgumentException('Invalid API path.');
        }
    }
}
