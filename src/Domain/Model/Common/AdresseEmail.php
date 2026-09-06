<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Common;

/**
 * A ECRIRE. A partir du moment ou un parametre est type `AdresseEmail`, vous savez qu'il
 * a ete valide. Vous n'avez plus jamais a le reverifier.
 *
 * Le constructeur doit etre prive : un client doit dire d'ou vient la valeur.
 * En cas de valeur invalide, levez `AdresseEmailInvalide::avecValeur()`.
 */
final readonly class AdresseEmail
{
    public static function depuisChaine(string $valeur): self
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function enChaine(): string
    {
        throw new \RuntimeException('TODO atelier 2');
    }

    public function estEgalA(self $autre): bool
    {
        throw new \RuntimeException('TODO atelier 2');
    }
}
