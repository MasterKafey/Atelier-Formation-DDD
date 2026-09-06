<?php

declare(strict_types=1);

namespace Bookshelf\Tests\Support;

use Bookshelf\Infrastructure\VatApi\VatApiTransport;

/**
 * Faux transport, pour tester la facade et la couche anticorruption sans reseau.
 *
 * Attention : ce test prouve que VOUS traduisez correctement une reponse donnee. Il ne
 * prouve pas que vatapi.com repond bien ainsi. Pour ca il faut un test d'adaptateur
 * contre le service reel, un bac a sable, ou un faux serveur HTTP.
 */
final class FakeVatApiTransport implements VatApiTransport
{
    /** @param array<string, mixed> $response */
    public function __construct(private readonly array $response)
    {
    }

    public function get(string $endpoint, array $query, string $apiKey): array
    {
        return $this->response;
    }
}
