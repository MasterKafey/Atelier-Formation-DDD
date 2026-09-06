<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\InMemory;

use Bookshelf\Application\CommanderLivrePapier\NiveauxDeStock;
use Bookshelf\Application\CommanderLivrePapier\Stock;
use Bookshelf\Domain\Model\Commande\IdentifiantLivre;

/**
 * Une seule classe pour les deux ports, lecture et ecriture : la connaissance du
 * stockage reste a un seul endroit. En production, ce serait une table et deux requetes.
 */
final class StockEnMemoire implements NiveauxDeStock, Stock
{
    /** @var array<string, int> */
    private array $exemplaires = [];

    public function ajouter(int $exemplaires): IdentifiantLivre
    {
        $identifiantLivre = IdentifiantLivre::generer();
        $this->ajouterAvecIdentifiant($identifiantLivre, $exemplaires);

        return $identifiantLivre;
    }

    public function ajouterAvecIdentifiant(IdentifiantLivre $identifiantLivre, int $exemplaires): void
    {
        $this->exemplaires[$identifiantLivre->enChaine()] = $exemplaires;
    }

    public function nombreExemplairesDisponibles(IdentifiantLivre $identifiantLivre): int
    {
        return $this->exemplaires[$identifiantLivre->enChaine()] ?? 0;
    }

    public function reserver(IdentifiantLivre $identifiantLivre, int $exemplaires): void
    {
        $this->exemplaires[$identifiantLivre->enChaine()] = $this->nombreExemplairesDisponibles($identifiantLivre) - $exemplaires;
    }
}
