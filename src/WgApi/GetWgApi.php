<?php

declare(strict_types=1);

namespace edrard\WgApi;

use InvalidArgumentException;
use SensitiveParameter;

/** Builds one World of Tanks URL from one prepared parameter set. */
final readonly class GetWgApi implements UrlBuilderInterface
{
    private ApiConfiguration $configuration;

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['configuration' => '[redacted]'];
    }

    /**
     * @param array<array-key, mixed> $applicationIds
     * @param array<array-key, mixed> $baseUrls
     */
    public function __construct(#[SensitiveParameter] array $applicationIds, array $baseUrls = [])
    {
        $this->configuration = new ApiConfiguration($applicationIds, $baseUrls);
    }

    /** @param array<array-key, mixed> $parameters */
    public function getUrl(string $server, string $type, string $target, #[SensitiveParameter] array $parameters = []): string
    {
        self::validatePath($type);
        self::validatePath($target);
        $realm = Realm::resolve($server);
        $query = [];
        foreach ($parameters as $name => $value) {
            if (!is_string($name) || $name === '' || preg_match('/[\x00-\x1f\x7f]/', $name)) {
                throw new InvalidArgumentException('Query parameter names must be non-empty strings.');
            }
            if ($name === 'application_id' || $value === null) {
                continue;
            }
            if (is_array($value)) {
                if (!array_is_list($value)) {
                    throw new InvalidArgumentException('Query arrays must be lists.');
                }
                foreach ($value as $item) {
                    if (!is_scalar($item)) {
                        throw new InvalidArgumentException('Query lists must contain scalar values.');
                    }
                }
                $value = implode(',', $value);
            } elseif (!is_scalar($value)) {
                throw new InvalidArgumentException('Query values must be scalar, null or lists.');
            }
            $query[$name] = $value;
        }
        $query['application_id'] = $this->configuration->applicationId($realm);

        return $this->configuration->baseUrl($realm).'/'.$type.'/'.$target.'/?'
            .http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private static function validatePath(string $path): void
    {
        if (!preg_match('~^[a-z0-9_]+(?:/[a-z0-9_]+)*$~D', $path)) {
            throw new InvalidArgumentException('Invalid API path.');
        }
    }
}
