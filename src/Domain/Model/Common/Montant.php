<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Common;

/**
 * A ECRIRE. Le value object le plus utile du projet.
 *
 * Un value object est IMMUABLE : chaque operation retourne une NOUVELLE instance.
 * C'est le piege classique de cet atelier ; le test `le_montant_d_origine_n_est_pas_modifie`
 * est la pour ca.
 *
 * `plus()` et `estSuperieurA()` doivent refuser deux devises differentes :
 * levez `MontantsNonComparables::carDevisesDifferentes()`.
 */
final readonly class Montant
{
    public static function depuisCentimes(int $montantEnCentimes, Devise $devise): self
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public static function zero(Devise $devise): self
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function multipliePar(int $facteur): self
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function plus(self $autre): self
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function avecTva(TauxDeTva $taux): self
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function estSuperieurA(self $autre): bool
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function enCentimes(): int
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function devise(): Devise
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    /** Ex. : "25,00 EUR" -> a formater proprement a l'atelier 3, pour le view model. */
    public function formate(): string
    {
        throw new \RuntimeException('TODO atelier 2');
    }
}
