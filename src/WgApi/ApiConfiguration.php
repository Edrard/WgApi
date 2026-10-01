<?php

declare(strict_types=1);

namespace edrard\WgApi;

use InvalidArgumentException;
use LogicException;
use SensitiveParameter;

final readonly class ApiConfiguration
{
    /** @var array<string, string> */
    private array $ids;
    /** @var array<string, string> */
    private array $urls;

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['applicationIds' => '[redacted]', 'origins' => '[redacted]'];
    }

    /**
     * @param array<array-key, mixed> $applicationIds
     * @param array<array-key, mixed> $baseUrls
     */
    public function __construct(#[SensitiveParameter] array $applicationIds, array $baseUrls = [])
    {
        $ids = [];
        foreach ($applicationIds as $realm => $id) {
            if (!is_string($realm) || !is_string($id) || trim($id) === '') {
                throw new InvalidArgumentException('Application IDs must be non-empty strings.');
            }
            $ids[Realm::resolve($realm)->value] = $id;
        }
        $urls = [
            'eu' => 'https://api.worldoftanks.eu',
            'na' => 'https://api.worldoftanks.com',
            'asia' => 'https://api.worldoftanks.asia',
        ];
        foreach ($baseUrls as $realm => $url) {
            if (!is_string($realm)) {
                throw new InvalidArgumentException('Base URL realm names must be strings.');
            }
            $parts = is_string($url) ? parse_url($url) : false;
            if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
                || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
                || !in_array($parts['path'] ?? '', ['', '/'], true)
                || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
                throw new InvalidArgumentException('API base URL must be an HTTPS origin.');
            }
            $urls[Realm::resolve($realm)->value] = rtrim($url, '/');
        }
        $this->ids = $ids;
        $this->urls = $urls;
    }

    public function applicationId(Realm $realm): string
    {
        return $this->ids[$realm->value] ?? throw new LogicException('Configure an application ID for realm '.$realm->value.'.');
    }

    public function baseUrl(Realm $realm): string
    {
        return $this->urls[$realm->value];
    }
}
