<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use RuntimeException;

final class CommandeIntrouvable extends RuntimeException
{
    public static function avecIdentifiant(IdentifiantCommande $identifiantCommande): self
    {
        return new self(sprintf('Commande introuvable : %s', $identifiantCommande->enChaine()));
    }
}
