<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Common;

enum Devise: string
{
    case EUR = 'EUR';
    case USD = 'USD';

    public function symbole(): string
    {
        return match ($this) {
            self::EUR => '€',
            self::USD => '$',
        };
    }
}
