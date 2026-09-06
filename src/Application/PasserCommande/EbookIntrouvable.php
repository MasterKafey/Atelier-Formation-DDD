<?php

declare(strict_types=1);

namespace Bookshelf\Application\PasserCommande;

use Bookshelf\Domain\Model\Commande\IdentifiantEbook;
use RuntimeException;

final class EbookIntrouvable extends RuntimeException
{
    public static function avecIdentifiant(IdentifiantEbook $identifiantEbook): self
    {
        return new self(sprintf('E-book introuvable : %s', $identifiantEbook->enChaine()));
    }
}
