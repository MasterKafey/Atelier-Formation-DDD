<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use RuntimeException;

final class AjoutDeLigneImpossible extends RuntimeException
{
    public static function carCommandeNonEnAttente(IdentifiantCommande $identifiantCommande): self
    {
        return new self(sprintf('La commande %s n\'est plus en attente', $identifiantCommande->enChaine()));
    }
}
