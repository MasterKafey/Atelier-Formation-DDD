<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * A ECRIRE. Value object d'identite : le type reel de l'identifiant devient un detail
 * interne. Le jour ou vous passez d'UUID v7 a autre chose, seule cette classe et
 * l'implementation du repository bougent.
 *
 * Modele : `IdentifiantEbook`, dans le meme repertoire.
 */
final readonly class IdentifiantCommande
{
    public static function depuisChaine(string $id): self
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public static function generer(): self
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function enChaine(): string
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function estEgalA(self $autre): bool
    {
        throw new \RuntimeException('TODO atelier 2');
    }
}
