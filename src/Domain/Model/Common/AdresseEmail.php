<?php

declare(strict_types=1);

namespace Bookshelf\Domain\Model\Common;

/**
 * CORRIGE. A partir du moment ou un parametre est type `AdresseEmail`, vous savez qu'il
 * a ete valide. Vous n'avez plus jamais a le reverifier.
 *
 * Le constructeur doit etre prive : un client doit dire d'ou vient la valeur.
 * En cas de valeur invalide, levez `AdresseEmailInvalide::avecValeur()`.
 */
final readonly class AdresseEmail
{
    private function __construct(private string $valeur)
    {
    }

    public static function depuisChaine(string $valeur): self
    {
        $valeur = trim($valeur);

        if (filter_var($valeur, FILTER_VALIDATE_EMAIL) === false) {
            throw AdresseEmailInvalide::avecValeur($valeur);
        }

        return new self($valeur);
    }

    public function enChaine(): string
    {
        return $this->valeur;
    }

    public function estEgalA(self $autre): bool
    {
        return $this->valeur === $autre->valeur;
    }
}
