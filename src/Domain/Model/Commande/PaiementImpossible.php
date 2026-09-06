<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use RuntimeException;

final class PaiementImpossible extends RuntimeException
{
    public static function carAnnulee(IdentifiantCommande $identifiantCommande): self
    {
        return new self(sprintf('La commande %s a ete annulee', $identifiantCommande->enChaine()));
    }

    public static function carDejaPayee(IdentifiantCommande $identifiantCommande): self
    {
        return new self(sprintf('La commande %s a deja ete payee', $identifiantCommande->enChaine()));
    }

    public static function carNonConfirmee(IdentifiantCommande $identifiantCommande): self
    {
        return new self(sprintf(
            'La commande %s n\'est pas confirmee : on ne paie pas un panier encore modifiable',
            $identifiantCommande->enChaine(),
        ));
    }
}
