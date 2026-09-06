<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * Identifiant d'un livre PAPIER, distinct de `IdentifiantEbook`.
 *
 * Deux value objects plutot qu'un seul « ItemId » generique : un e-book et un livre
 * papier n'obeissent pas aux memes regles. L'un est duplicable a l'infini, l'autre a un
 * stock fini et non reapprovisionnable. Les confondre reviendrait a perdre cette
 * distinction dans le typage, et donc a la voir ressurgir en `if` un peu partout.
 */
final readonly class IdentifiantLivre
{
    private function __construct(private string $id)
    {
    }

    public static function depuisChaine(string $id): self
    {
        Assert::uuid($id, 'Identifiant de livre invalide : %s');

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
