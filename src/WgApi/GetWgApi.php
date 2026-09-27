<?php

declare(strict_types=1);

namespace edrard\WgApi;

use InvalidArgumentException;
use SensitiveParameter;

/** Existing method names over a validated, extensible URL builder. */
class GetWgApi implements UrlBuilderInterface
{
    private string $prefix = '';
    private ApiConfiguration $configuration;
    /** @var array<string, Endpoint> */
    private array $endpoints;

    /**
     * @param array<array-key, mixed> $ids
     * @param array<array-key, mixed> $config
     */
    public function __construct(array $ids = [], array $config = [])
    {
        $this->configuration = new ApiConfiguration($ids, $config);
        $this->endpoints = [
            'getPlayerId' => new Endpoint('account/list', 'search', 1, numeric: false),
            'getPlayerStat' => new Endpoint('account/info', 'account_id', 100),
            'getPlayerTankStat' => new Endpoint('account/tanks', 'account_id', 100),
            'getPlayerAchiv' => new Endpoint('account/achievements', 'account_id', 100),
            'getPlayerTankStatFull' => new Endpoint('tanks/stats', 'account_id', 1),
        ];
    }

    /** @deprecated Inject a logger in the HTTP/data layer instead. */
    public function fullLog(): void
    {
    }

    /**
     * @param array<array-key, mixed> $ids
     */
    public function changeIds(array $ids = []): void
    {
        $this->configuration->changeIds($ids);
    }
    public function changeUrlPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }
    public function getUrlPrefix(): string
    {
        return $this->prefix;
    }

    public function registerEndpoint(string $name, Endpoint $endpoint): void
    {
        if ($name === '') {
            throw new InvalidArgumentException('Endpoint name must not be empty.');
        }
        $this->endpoints[$name] = $endpoint;
    }

    /**
     * @param array<array-key, mixed> $fields
     */
    public function getUrl(string $server, string $type, string $target, #[SensitiveParameter] array $fields = []): string
    {
        Endpoint::validatePath($type);
        Endpoint::validatePath($target);
        $realm = Realm::resolve($server);
        $fields['application_id'] = $this->configuration->applicationId($realm);
        $fields['language'] ??= $this->configuration->language($realm);
        foreach ($fields as $key => &$value) {
            if (!is_string($key) || $key === '' || preg_match('/[\x00-\x1f\x7f]/', $key)) {
                throw new InvalidArgumentException('Query parameter names must be non-empty strings.');
            }
            if (is_array($value)) {
                foreach ($value as $item) {
                    if (!is_scalar($item)) {
                        throw new InvalidArgumentException('Query lists must contain scalar values.');
                    }
                }
                $value = implode(',', $value);
            } elseif ($value !== null && !is_scalar($value)) {
                throw new InvalidArgumentException('Query values must be scalar, null or lists.');
            }
        }
        unset($value);
        return $this->configuration->baseUrl($realm).'/'.$type.'/'.$target.'/?'
            .http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param array<array-key, mixed> $ids
     * @param array<array-key, mixed> $types
     * @param array<array-key, mixed> $extra
     * @return array<int|string, string>
     */
    public function prepareBatch(string $method, string $server, array $ids, array $types = [], #[SensitiveParameter] array $extra = [], int|false $max = false): array
    {
        $endpoint = $this->endpoints[$method] ?? throw new InvalidArgumentException('Unknown endpoint: '.$method);
        Realm::resolve($server);
        if ($max !== false && $max < 1) {
            throw new InvalidArgumentException('Batch size must be positive.');
        }
        foreach ($ids as $id) {
            if ($endpoint->numeric) {
                if ((!is_int($id) && !is_string($id)) || !ctype_digit((string) $id) || filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                    throw new InvalidArgumentException('IDs must be positive integers without overflow.');
                }
            } elseif (!is_string($id) || trim($id) === '') {
                throw new InvalidArgumentException('Search terms must be non-empty strings.');
            }
        }
        if ($types !== []) {
            $extra['extra'] = $types;
        }
        $urls = [];
        foreach (array_chunk(array_values(array_unique($ids, SORT_REGULAR)), $max === false ? $endpoint->limit : min($max, $endpoint->limit)) as $index => $chunk) {
            $fields = array_replace($extra, [$endpoint->parameter => $chunk]);
            $urls[$this->prefix.$index] = $this->getUrl($server, $endpoint->api, $endpoint->path, $fields);
        }
        return $urls;
    }

    /**
     * @param array<array-key, mixed> $ids
     */
    public function addServerBaseId(array &$ids, string $server): void
    {
        $base = $this->configuration->accountOffset(Realm::resolve($server));
        $next = $ids;
        foreach ($ids as $key => $id) {
            if (!is_int($id) || $id < 0 || $id > PHP_INT_MAX - $base) {
                throw new InvalidArgumentException('Relative account IDs must be non-negative integers without overflow.');
            }
            $next[$key] = $id + $base;
        }
        $ids = $next;
    }

    /**
     * @param array<array-key, mixed> $names
     * @param array<array-key, mixed> $extra
     * @return array<int|string, string>
     */
    public function getPlayerId(string $server, array $names, #[SensitiveParameter] array $extra = [], int|false $max = false): array
    {
        return $this->prepareBatch(__FUNCTION__, $server, $names, [], $extra, $max);
    }
    /**
     * @param array<array-key, mixed> $ids
     * @param array<array-key, mixed> $types
     * @param array<array-key, mixed> $extra
     * @return array<int|string, string>
     */
    public function getPlayerStat(string $server, array $ids, array $types = [], #[SensitiveParameter] array $extra = [], int|false $max = false): array
    {
        return $this->prepareBatch(__FUNCTION__, $server, $ids, $types, $extra, $max);
    }
    /**
     * @param array<array-key, mixed> $ids
     * @param array<array-key, mixed> $types
     * @param array<array-key, mixed> $extra
     * @return array<int|string, string>
     */
    public function getPlayerTankStat(string $server, array $ids, array $types = [], #[SensitiveParameter] array $extra = [], int|false $max = false): array
    {
        return $this->prepareBatch(__FUNCTION__, $server, $ids, $types, $extra, $max);
    }
    /**
     * @param array<array-key, mixed> $ids
     * @param array<array-key, mixed> $types
     * @param array<array-key, mixed> $extra
     * @return array<int|string, string>
     */
    public function getPlayerTankStatFull(string $server, array $ids, array $types = [], #[SensitiveParameter] array $extra = [], int|false $max = false): array
    {
        return $this->prepareBatch(__FUNCTION__, $server, $ids, $types, $extra, $max);
    }
    /**
     * @param array<array-key, mixed> $ids
     * @param array<array-key, mixed> $types
     * @param array<array-key, mixed> $extra
     * @return array<int|string, string>
     */
    public function getPlayerAchiv(string $server, array $ids, array $types = [], #[SensitiveParameter] array $extra = [], int|false $max = false): array
    {
        return $this->prepareBatch(__FUNCTION__, $server, $ids, $types, $extra, $max);
    }
}
