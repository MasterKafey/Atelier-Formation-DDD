<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Common;

use Webmozart\Assert\Assert;

/**
 * A ECRIRE. Le taux applicable depend du pays de l'acheteur : c'est un service externe
 * qui le fournira (atelier 3). Ici, on encapsule seulement le pourcentage et le calcul.
 *
 * `appliquerA()` recoit un montant en centimes et rend le montant TTC en centimes.
 * Attention a l'arrondi : c'est la seule implementation du calcul dans toute
 * l'application, elle doit etre juste.
 */
final readonly class TauxDeTva
{
    public static function depuisPourcentage(int $pourcentage): self
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function enPourcentage(): int
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function appliquerA(int $montantEnCentimes): int
    {
        throw new \RuntimeException('TODO atelier 2');
    }
}
