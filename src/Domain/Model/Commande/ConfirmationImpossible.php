<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use RuntimeException;

final class ConfirmationImpossible extends RuntimeException
{
    public static function carAucuneLigne(IdentifiantCommande $identifiantCommande): self
    {
        return new self(sprintf('La commande %s n\'a aucune ligne', $identifiantCommande->enChaine()));
    }

    public static function carCommandeNonEnAttente(IdentifiantCommande $identifiantCommande): self
    {
        return new self(sprintf(
            'La commande %s n\'est plus en attente : elle ne peut plus etre confirmee',
            $identifiantCommande->enChaine(),
        ));
    }
}
