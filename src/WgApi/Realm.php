<?php

declare(strict_types=1);

namespace edrard\WgApi;

use InvalidArgumentException;

enum Realm: string
{
    case EU = 'eu';
    case NA = 'na';
    case ASIA = 'asia';
    public static function resolve(string $realm): self
    {
        return match (strtolower($realm)) {
            'eu' => self::EU,
            'na', 'us' => self::NA,
            'asia', 'sea' => self::ASIA,
            default => throw new InvalidArgumentException('Unsupported Wargaming realm. Use eu, na or asia.'),
        };
    }
}
