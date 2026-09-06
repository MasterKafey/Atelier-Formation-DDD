<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use Bookshelf\Domain\Model\Common\AdresseEmail;
use Bookshelf\Domain\Model\Common\Montant;

/** Domain event. Immuable, nomme au passe, ne contient que des objets de domaine. */
final readonly class CommandePassee
{
    public function __construct(
        public IdentifiantCommande $identifiantCommande,
        public AdresseEmail $adresseEmail,
        public Montant $total,
    ) {
    }
}
