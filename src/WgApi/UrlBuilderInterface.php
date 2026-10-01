<?php

declare(strict_types=1);

namespace edrard\WgApi;

interface UrlBuilderInterface
{
    /** @param array<array-key, mixed> $parameters */
    public function getUrl(string $server, string $type, string $target, array $parameters = []): string;
}
