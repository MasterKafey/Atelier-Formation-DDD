<?php

declare(strict_types=1);

namespace Bookshelf\Infrastructure\InMemory;

use Bookshelf\Domain\Model\Commande\Commande;
use Bookshelf\Domain\Model\Commande\CommandeIntrouvable;
use Bookshelf\Domain\Model\Commande\CommandeRepository;
use Bookshelf\Domain\Model\Commande\IdentifiantCommande;

/**
 * CORRIGE. Quinze lignes : c'est ce que coute une interface, et c'est ce qui rendra
 * possible toute la suite de tests de cas d'usage de l'atelier 3.
 */
final class CommandeRepositoryEnMemoire implements CommandeRepository
{
    /** @var array<string, Commande> */
    private array $commandes = [];

    public function prochainIdentifiant(): IdentifiantCommande
    {
        return IdentifiantCommande::generer();
    }

    public function enregistrer(Commande $commande): void
    {
        $this->commandes[$commande->identifiantCommande()->enChaine()] = $commande;
    }

    public function parIdentifiant(IdentifiantCommande $identifiantCommande): Commande
    {
        return $this->commandes[$identifiantCommande->enChaine()]
            ?? throw CommandeIntrouvable::avecIdentifiant($identifiantCommande);
    }
}
