<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Common;

use LogicException;

final class MontantsNonComparables extends LogicException
{
    public static function carDevisesDifferentes(Devise $a, Devise $b): self
    {
        return new self(sprintf('Devises differentes : %s et %s', $a->value, $b->value));
    }
}
