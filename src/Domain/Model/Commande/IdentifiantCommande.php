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

    /**
     * CONCESSION A L'ORM, et il faut savoir la nommer.
     *
     * Doctrine construit sa carte d'identite en convertissant l'identifiant en chaine.
     * Un value object en cle primaire doit donc etre convertible en chaine, sans quoi
     * `persist()` echoue avec « Object of class IdentifiantCommande could not be converted to string ».
     *
     * C'est du code technique dans une classe de domaine. Le compromis reste acceptable :
     * la methode n'introduit aucune dependance, ne demande aucun contexte, et `IdentifiantCommande`
     * reste instanciable et testable sans base de donnees.
     */
    public function __toString(): string
    {
        return $this->id;
    }
}
