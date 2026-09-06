<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

/**
 * CORRIGE. Invariant : une quantite vaut au moins 1.
 * En cas de valeur invalide, levez `QuantiteInvalide::carAuMoinsUn()`.
 */
final readonly class Quantite
{
    private function __construct(private int $valeur)
    {
    }

    public static function depuisEntier(int $valeur): self
    {
        if ($valeur < 1) {
            throw QuantiteInvalide::carAuMoinsUn($valeur);
        }

        return new self($valeur);
    }

    public function enEntier(): int
    {
        return $this->valeur;
    }
}
