<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Common;

use Webmozart\Assert\Assert;

/**
 * CORRIGE. Le taux applicable depend du pays de l'acheteur : c'est un service externe
 * qui le fournira (atelier 3). Ici, on encapsule seulement le pourcentage et le calcul.
 *
 * `appliquerA()` recoit un montant en centimes et rend le montant TTC en centimes.
 * Attention a l'arrondi : c'est la seule implementation du calcul dans toute
 * l'application, elle doit etre juste.
 */
final readonly class TauxDeTva
{
    private function __construct(private int $pourcentage)
    {
    }

    public static function depuisPourcentage(int $pourcentage): self
    {
        Assert::range($pourcentage, 0, 100, 'Taux de TVA hors bornes : %s');

        return new self($pourcentage);
    }

    public function enPourcentage(): int
    {
        return $this->pourcentage;
    }

    public function appliquerA(int $montantEnCentimes): int
    {
        return (int) round($montantEnCentimes * (100 + $this->pourcentage) / 100);
    }
}
