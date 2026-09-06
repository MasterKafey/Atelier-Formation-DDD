<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\VatApi;

/**
 * Le seul point de contact reseau. Isole pour qu'on puisse tester la facade `VatApi`
 * et la couche anticorruption sans appeler vatapi.com.
 */
interface VatApiTransport
{
    /**
     * @param array<string, string|null> $query
     *
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $query, string $apiKey): array;
}
