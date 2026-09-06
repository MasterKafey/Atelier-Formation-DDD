<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

/**
 * A ECRIRE. Invariant : une quantite vaut au moins 1.
 * En cas de valeur invalide, levez `QuantiteInvalide::carAuMoinsUn()`.
 */
final readonly class Quantite
{
    public static function depuisEntier(int $valeur): self
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function enEntier(): int
    {
        throw new \RuntimeException('TODO atelier 2');
    }
}
