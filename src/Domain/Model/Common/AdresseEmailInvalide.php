<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Common;

use InvalidArgumentException;

final class AdresseEmailInvalide extends InvalidArgumentException
{
    public static function avecValeur(string $valeur): self
    {
        return new self(sprintf('Adresse e-mail invalide : %s', $valeur));
    }
}
