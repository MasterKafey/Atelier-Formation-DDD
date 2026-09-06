<?php

declare(strict_types=1);

namespace Bookshelf\Application\CommanderLivrePapier;

use Bookshelf\Domain\Model\Commande\IdentifiantLivre;

/** Port sortant d'ECRITURE : « pour reserver des exemplaires ». */
interface Stock
{
    public function reserver(IdentifiantLivre $identifiantLivre, int $exemplaires): void;
}
