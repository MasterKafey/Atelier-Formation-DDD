<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\VatApi;

use RuntimeException;

/**
 * On ne rend jamais un tableau brut a un client : un tableau n'a pas de forme definie, et
 * `$data['rates']['electronic']['rate']` produira une erreur le jour ou l'API changera.
 * Un objet, lui, a un jeu de methodes connu et valide sa donnee a la construction.
 */
final readonly class VatRateCheckResult
{
    /** @param array<string, mixed> $responseData */
    public function __construct(private array $responseData)
    {
        if (($responseData['status'] ?? null) !== 200) {
            throw new RuntimeException('Reponse inattendue de vatapi.com');
        }
    }

    public function rate(string $fallbackType): int
    {
        if (($this->responseData['filter_match'] ?? false) === true) {
            return (int) $this->responseData['rate'];
        }

        return (int) $this->responseData['rates'][$fallbackType]['rate'];
    }
}
