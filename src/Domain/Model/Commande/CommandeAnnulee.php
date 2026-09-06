<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;


/** Domain event. Immuable, nomme au passe, ne contient que des objets de domaine. */
final readonly class CommandeAnnulee
{
    public function __construct(
        public IdentifiantCommande $identifiantCommande,
    ) {
    }
}
