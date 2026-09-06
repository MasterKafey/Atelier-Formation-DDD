<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * CORRIGE. Value object d'identite : le type reel de l'identifiant devient un detail
 * interne. Le jour ou vous passez d'UUID v7 a autre chose, seule cette classe et
 * l'implementation du repository bougent.
 *
 * Modele : `IdentifiantEbook`, dans le meme repertoire.
 */
final readonly class IdentifiantCommande
{
    private function __construct(private string $id)
    {
    }

    public static function depuisChaine(string $id): self
    {
        Assert::uuid($id, 'Identifiant de commande invalide : %s');

        return new self($id);
    }

    public static function generer(): self
    {
        return new self(Uuid::v7()->toRfc4122());
    }

    public function enChaine(): string
    {
        return $this->id;
    }

    public function estEgalA(self $autre): bool
    {
        return $this->id === $autre->id;
    }
}
