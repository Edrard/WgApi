<?php

declare(strict_types=1);

namespace edrard\WgApi;

interface UrlBuilderInterface
{
    /**
     * @param array<array-key, mixed> $fields
     */
    public function getUrl(string $server, string $type, string $target, array $fields = []): string;
    /**
     * @param array<array-key, mixed> $ids
     * @param array<array-key, mixed> $types
     * @param array<array-key, mixed> $extra
     * @return array<int|string, string>
     */
    public function prepareBatch(string $method, string $server, array $ids, array $types = [], array $extra = [], int|false $max = false): array;
    public function changeUrlPrefix(string $prefix): void;
    public function getUrlPrefix(): string;
    /**
     * @param array<array-key, mixed> $ids
     */
    public function addServerBaseId(array &$ids, string $server): void;
}
