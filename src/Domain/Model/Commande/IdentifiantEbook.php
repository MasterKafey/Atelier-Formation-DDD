<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * Fourni comme modele. `IdentifiantCommande` a exactement la meme forme : ecrivez-le vous-meme,
 * c'est le meilleur moyen de retenir la structure d'un value object d'identite.
 */
final readonly class IdentifiantEbook
{
    private function __construct(private string $id)
    {
    }

    public static function depuisChaine(string $id): self
    {
        Assert::uuid($id, 'Identifiant d\'e-book invalide : %s');

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
