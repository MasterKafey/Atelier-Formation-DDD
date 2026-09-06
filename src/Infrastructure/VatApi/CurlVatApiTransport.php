<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\VatApi;

use RuntimeException;

/**
 * Implementation de production. Fournie a titre de reference, NON couverte par un test
 * unitaire : c'est du code d'infrastructure, il se teste avec un test d'adaptateur contre
 * le service reel, un bac a sable, ou un faux serveur que vous demarrez.
 *
 * Ne mockez pas ce que vous ne possedez pas.
 */
final class CurlVatApiTransport implements VatApiTransport
{
    public function __construct(private readonly string $baseUrl = 'https://eu.vatapi.com/v2')
    {
    }

    public function get(string $endpoint, array $query, string $apiKey): array
    {
        $curl = curl_init($this->baseUrl . $endpoint . '?' . http_build_query($query));

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['x-api-key: ' . $apiKey],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error !== '') {
            throw new RuntimeException('Appel a vatapi.com impossible : ' . $error);
        }

        return json_decode((string) $response, true, flags: JSON_THROW_ON_ERROR);
    }
}
