<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Common;

use Webmozart\Assert\Assert;

/** Code pays ISO 3166-1 alpha-2. Fourni : aucune regle metier a ecrire ici. */
final readonly class CodePays
{
    private function __construct(private string $code)
    {
    }

    public static function depuisChaine(string $code): self
    {
        $code = strtoupper(trim($code));
        Assert::regex($code, '/^[A-Z]{2}$/', 'Code pays invalide : %s');

        return new self($code);
    }

    public function enChaine(): string
    {
        return $this->code;
    }

    public function estEgalA(self $autre): bool
    {
        return $this->code === $autre->code;
    }
}
