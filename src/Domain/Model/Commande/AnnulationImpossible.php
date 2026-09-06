<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use RuntimeException;

final class AnnulationImpossible extends RuntimeException
{
    public static function carDejaPayee(IdentifiantCommande $identifiantCommande): self
    {
        return new self(sprintf('La commande %s a deja ete payee', $identifiantCommande->enChaine()));
    }

    public static function carDejaAnnulee(IdentifiantCommande $identifiantCommande): self
    {
        return new self(sprintf('La commande %s est deja annulee', $identifiantCommande->enChaine()));
    }
}
