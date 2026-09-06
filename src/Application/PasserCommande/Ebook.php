<?php

declare(strict_types=1);

namespace Bookshelf\Application\PasserCommande;

use Bookshelf\Domain\Model\Commande\IdentifiantEbook;
use Bookshelf\Domain\Model\Common\Montant;

/**
 * Read model INTERNE : la donnee ne sort pas de l'application, elle sert au calcul.
 * A ce titre il expose des value objects, contrairement au view model de
 * `ListerEbooksDisponibles` qui, lui, expose des primitifs deja formates.
 *
 * Il porte le meme nom que l'entite `Ebook` du domaine, dans un autre namespace. Ce n'est
 * pas de la duplication : chaque classe est possedee par son contexte d'usage.
 */
final readonly class Ebook
{
    public function __construct(
        private IdentifiantEbook $identifiantEbook,
        private Montant $prix,
    ) {
    }

    public function identifiantEbook(): IdentifiantEbook
    {
        return $this->identifiantEbook;
    }

    public function prix(): Montant
    {
        return $this->prix;
    }
}
