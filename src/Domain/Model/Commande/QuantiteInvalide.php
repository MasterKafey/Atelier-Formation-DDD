<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Commande;

use InvalidArgumentException;

final class QuantiteInvalide extends InvalidArgumentException
{
    public static function carAuMoinsUn(int $valeur): self
    {
        return new self(sprintf('La quantite doit valoir au moins 1, recu : %d', $valeur));
    }
}
