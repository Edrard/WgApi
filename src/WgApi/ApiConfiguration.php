<?php

declare(strict_types=1);

namespace edrard\WgApi;

use InvalidArgumentException;
use LogicException;

final class ApiConfiguration
{
    /** @var array<string, string> */
    private array $ids = [];
    /** @var array<string, string> */
    private array $urls = ['eu' => 'https://api.worldoftanks.eu', 'na' => 'https://api.worldoftanks.com', 'asia' => 'https://api.worldoftanks.asia'];
    /** @var array<string, string> */
    private array $languages = ['eu' => 'en', 'na' => 'en', 'asia' => 'en'];
    /** @var array<string, int> */
    private array $offsets = ['eu' => 500000000, 'na' => 1000000000, 'asia' => 2000000000];

    /**
     * @param array<array-key, mixed> $ids
     * @param array<array-key, mixed> $config
     */
    public function __construct(array $ids, array $config = [])
    {
        $this->changeIds($config['id'] ?? []);
        $this->changeIds($ids);
        foreach (['url' => 'urls', 'lang' => 'languages', 'start' => 'offsets'] as $key => $property) {
            foreach ($config[$key] ?? [] as $realm => $value) {
                $canonical = Realm::resolve($realm)->value;
                if ($key === 'url') {
                    $parts = is_string($value) ? parse_url($value) : false;
                    if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
                        || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
                        || !in_array($parts['path'] ?? '', ['', '/'], true)
                        || preg_match('/[\x00-\x20\x7f\\\\]/', $value)) {
                        throw new InvalidArgumentException('API base URL must be an HTTPS origin.');
                    }
                    $value = rtrim($value, '/');
                } elseif ($key === 'start') {
                    if (!is_int($value) || $value < 0) {
                        throw new InvalidArgumentException('Account offset must be a non-negative integer.');
                    }
                } elseif (!is_string($value) || $value === '') {
                    throw new InvalidArgumentException('Language must be a non-empty string.');
                }
                $this->{$property}[$canonical] = $value;
            }
        }
    }
    /**
     * @param array<array-key, mixed> $ids
     */
    public function changeIds(array $ids): void
    {
        $validated = [];
        foreach ($ids as $realm => $id) {
            if (!is_string($id) || trim($id) === '') {
                throw new InvalidArgumentException('Application IDs must be non-empty strings.');
            }
            $validated[Realm::resolve($realm)->value] = $id;
        }
        $this->ids = array_replace($this->ids, $validated);
    }
    public function applicationId(Realm $realm): string
    {
        return $this->ids[$realm->value] ?? throw new LogicException('Configure an application ID for realm '.$realm->value.'.');
    }
    public function baseUrl(Realm $realm): string
    {
        return $this->urls[$realm->value];
    }
    public function language(Realm $realm): string
    {
        return $this->languages[$realm->value];
    }
    public function accountOffset(Realm $realm): int
    {
        return $this->offsets[$realm->value];
    }
}
